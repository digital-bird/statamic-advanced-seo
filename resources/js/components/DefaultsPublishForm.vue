<template>

    <div>
        <breadcrumb v-if="breadcrumbs" :url="breadcrumbs[0].url" :title="breadcrumbs[0].text" />

        <div class="flex items-center mb-6">
            <h1 class="flex-1" v-text="title" />

            <div class="flex pt-px mr-4 text-gray-600 text-2xs" v-if="readOnly">
                <svg-icon name="lock" class="w-4 mr-1 -mt-1" /> {{ __('Read Only') }}
            </div>

            <button
                v-if="!readOnly"
                class="btn-primary"
                :disabled="!canSave"
                @click.prevent="save"
                v-text="__('Save Changes')"
            />
        </div>

        <publish-container
            v-if="fieldset"
            ref="container"
            :name="publishContainer"
            :blueprint="fieldset"
            :values="values"
            :reference="initialReference"
            :meta="meta"
            :errors="errors"
            :site="site"
            :localized-fields="localizedFields"
            :is-root="isRoot"
            :track-dirty-state="trackDirtyState"
            @updated="values = $event"
        >
            <div>
                <publish-tabs
                    :read-only="readOnly"
                    :syncable="hasOrigin"
                    @updated="setFieldValue"
                    @meta-updated="setFieldMeta"
                    @synced="syncField"
                    @desynced="desyncField"
                    @focus="container.$emit('focus', $event)"
                    @blur="container.$emit('blur', $event)"
                >
                    <template #actions="{ shouldShowSidebar }">
                        <div class="p-4 card" v-if="localizations.length > 1">
                            <label class="mb-2 font-medium publish-field-label" v-text="__('Sites')" />
                            <div
                                v-for="option in localizations"
                                :key="option.handle"
                                class="flex items-center px-4 py-2 -mx-4 text-sm cursor-pointer"
                                :class="option.active ? 'bg-blue-100 dark:bg-dark-300' : 'hover:bg-gray-200 dark:hover:bg-dark-400'"
                                @click="localizationSelected(option)"
                            >
                                <div class="flex items-center flex-1" :class="{ 'line-through': !option.exists }">
                                    <span class="mr-2 little-dot" :class="{
                                        'bg-green-600': option.published,
                                        'bg-gray-500': !option.published,
                                        'bg-red-500': !option.exists
                                    }" />
                                    {{ option.name }}
                                    <loading-graphic
                                        :size="14"
                                        text=""
                                        class="ml-2"
                                        v-if="localizing && localizing.handle === option.handle" />
                                </div>
                                <div class="badge-sm bg-orange dark:bg-orange-dark" v-if="option.origin" v-text="__('Origin')" />
                                <div class="badge-sm bg-blue dark:bg-dark-blue-175" v-if="option.active" v-text="__('Active')" />
                                <div class="badge-sm bg-purple dark:bg-purple-dark" v-if="option.root && !option.origin && !option.active" v-text="__('Root')" />
                            </div>
                        </div>
                    </template>
                </publish-tabs>
            </div>

        </publish-container>
    </div>

</template>

<script>
import HasHiddenFields from '../../../vendor/statamic/cms/resources/js/components/publish/HasHiddenFields';

export default {

    mixins: [
        HasHiddenFields,
    ],

    props: {
        publishContainer: String,
        initialReference: String,
        initialFieldset: Object,
        initialValues: Object,
        initialMeta: Object,
        initialTitle: String,
        initialLocalizations: Array,
        initialLocalizedFields: Array,
        initialHasOrigin: Boolean,
        initialOriginValues: Object,
        initialOriginMeta: Object,
        initialSite: String,
        breadcrumbs: Array,
        initialActions: Object,
        method: String,
        isCreating: Boolean,
        initialReadOnly: Boolean,
        initialIsRoot: Boolean,
        contentType: String,
    },

    data() {
        return {
            actions: this.initialActions,
            saving: false,
            localizing: false,
            trackDirtyState: true,
            fieldset: this.initialFieldset,
            title: this.initialTitle,
            values: _.clone(this.initialValues),
            meta: _.clone(this.initialMeta),
            localizations: _.clone(this.initialLocalizations),
            localizedFields: this.initialLocalizedFields,
            hasOrigin: this.initialHasOrigin,
            originValues: this.initialOriginValues || {},
            originMeta: this.initialOriginMeta || {},
            site: this.initialSite,
            error: null,
            errors: {},
            isRoot: this.initialIsRoot,
            readOnly: this.initialReadOnly,

            quickSaveKeyBinding: null,
            quickSave: false,
        }
    },

    computed: {

        hasErrors() {
            return this.error || Object.keys(this.errors).length;
        },

        somethingIsLoading() {
            return ! this.$progress.isComplete();
        },

        canSave() {
            return !this.readOnly && this.isDirty && !this.somethingIsLoading;
        },

        isBase() {
            return this.publishContainer === 'base';
        },

        isDirty() {
            return this.$dirty.has(this.publishContainer);
        },

    },

    watch: {

        saving(saving) {
            this.$progress.loading(`${this.publishContainer}-defaults-publish-form`, saving);
        }

    },

    methods: {

        clearErrors() {
            this.error = null;
            this.errors = {};
        },

        save() {
            if (! this.canSave) {
                this.quickSave = false;
                return;
            }

            this.saving = true;
            this.clearErrors();

            const payload = { ...this.visibleValues, ...{
                blueprint: this.fieldset.handle,
                _localized: this.localizedFields,
            }};

            this.$axios[this.method](this.actions.save, payload).then(response => {
                this.saving = false;
                if (!this.isCreating) this.$toast.success(__('Saved'));
                this.$refs.container.saved();
                this.$nextTick(() => this.$emit('saved', response));
                this.quickSave = false;
            }).catch(e => this.handleAxiosError(e));
        },

        handleAxiosError(e) {
            this.saving = false;
            if (e.response && e.response.status === 422) {
                const { message, errors } = e.response.data;
                this.error = message;
                this.errors = errors;
                this.$toast.error(message);
            } else if (e.response) {
                this.$toast.error(e.response.data.message);
            } else {
                this.$toast.error(e || 'Something went wrong');
            }
        },

        localizationSelected(localization) {
            if (localization.active) return;

            if (this.isDirty) {
                if (! confirm(__('Are you sure? Unsaved changes will be lost.'))) {
                    return;
                }
            }

            this.$dirty.remove(this.publishContainer);

            this.localizing = localization;

            this.editLocalization(localization);

            if (this.isBase) {
                window.history.replaceState({}, '', localization.url);
            }
        },

        editLocalization(localization) {
            return this.$axios.get(localization.url).then(response => {
                clearTimeout(this.trackDirtyStateTimeout);
                this.trackDirtyState = false;

                const data = response.data;
                this.values = data.values;
                this.originValues = data.originValues;
                this.originMeta = data.originMeta;
                this.meta = data.meta;
                this.localizations = data.localizations;
                this.localizedFields = data.localizedFields;
                this.hasOrigin = data.hasOrigin;
                this.actions = data.actions;
                this.fieldset = data.blueprint;
                this.isRoot = data.isRoot;
                this.site = localization.handle;
                this.localizing = false;

                this.trackDirtyStateTimeout = setTimeout(() => this.trackDirtyState = true, 300); // after any fieldtypes do a debounced update
            })
        },

        setFieldValue(handle, value) {
            if (this.hasOrigin) this.desyncField(handle);

            this.$refs.container.setFieldValue(handle, value);
        },

        syncField(handle) {
            if (! confirm(__('Are you sure? This field\'s value will be replaced by the value in the original entry.')))
                return;

            this.localizedFields = this.localizedFields.filter(field => field !== handle);
            this.$refs.container.setFieldValue(handle, this.originValues[handle]);

            // Update the meta for this field. For instance, a relationship field would have its data preloaded into it.
            // If you sync the field, the preloaded data would be outdated and an ID would show instead of the titles.
            this.meta[handle] = this.originMeta[handle];
        },

        desyncField(handle) {
            if (!this.localizedFields.includes(handle))
                this.localizedFields.push(handle);

            this.$refs.container.dirty();
        },

    },

    mounted() {
        this.quickSaveKeyBinding = this.$keys.bindGlobal(['mod+s'], e => {
            e.preventDefault();
            this.quickSave = true;
            this.save();
        });
    },

    created() {
        window.history.replaceState({}, document.title, document.location.href.replace('created=true', ''));
    },

    unmounted() {
        clearTimeout(this.trackDirtyStateTimeout);
    },

    destroyed() {
        this.quickSaveKeyBinding.destroy();
    }

}
</script>
