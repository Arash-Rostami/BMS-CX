<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :label="$field->getLabel()"
    :required="$field->isRequired()"
    :helper-text="$field->getHelperText()"
>
    <div x-data="{
            templates: @js(\App\Services\QuoteRequestTemplates::getEmailBody()),
            currentTemplateIndex: 0,
            get templateKeys() {
                return Object.keys(this.templates);
            },
            nextTemplate() {
                this.currentTemplateIndex = (this.currentTemplateIndex + 1) % this.templateKeys.length;
                $wire.set('data.extra.details', this.currentTemplate);
            },
            get currentTemplate() {
                return this.templates[this.templateKeys[this.currentTemplateIndex]];
            },
            get currentTemplateName() {
                return this.templateKeys[this.currentTemplateIndex];
            }
        }">
        <button
            type="button"
            @click="nextTemplate()"
            class="inline-flex items-center px-4 py-2 bg-primary-500 dark:bg-primary-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-white uppercase hover:bg-primary-400 dark:hover:bg-primary-400 focus:bg-primary-400 dark:focus:bg-primary-400 active:bg-primary-600 dark:active:bg-primary-600 focus:outline-none transition ease-in-out duration-150"
        >
            Create ✨  |&nbsp;<small x-text="currentTemplateName"> </small>
        </button>
    </div>
</x-dynamic-component>
