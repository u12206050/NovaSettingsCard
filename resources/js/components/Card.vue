<template>
    <div class="">

        <h1 class="mb-3 text-90 font-normal text-2xl flex">{{ cardName }}</h1>

        <card v-if="theTab">
            <div class="tabs-wrap border-b-2 border-40 w-full">
                <div class="tabs flex flex-row overflow-x-auto">
                    <button
                        class="py-5 px-8 border-b-2 focus:outline-none tab"
                        :class="[theTab.key == tab.key ? 'text-grey-black font-bold border-primary': 'text-grey font-semibold border-40']"
                        v-for="(tab, key) in card.fields"
                        :key="key"
                        @click="selectTab(tab)">
                        {{ tab.name }}
                    </button>
                </div>
            </div>

            <div
                v-if="theTab"
                :ref="theTab.key"
                :label="theTab.name"
                :key="'related-tabs-fields' + index"
                >

                <div>
                    <form autocomplete="off" v-on:submit.prevent="saveSettings">
                        <template v-for="field in theTab.fields">
                            <component
                                :key="field.attribute"
                                :is="'form-' + field.component"
                                :errors="errorData"
                                :resource-name="resource"
                                :field="field"
                            />
                        </template>

                        <div class="bg-30 flex px-8 py-4">

                            <button class="ml-auto btn btn-default btn-primary inline-flex items-center relative">
                                <span :class="{'invisible': working}">
                                    {{ __('Save settings') }}
                                </span>

                                <span
                                    v-if="working"
                                    class="absolute"
                                    style="top: 50%; left: 50%; transform: translate(-50%, -50%)"
                                >
                                    <loader class="text-white" width="32" />
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </card>
    </div>
</template>

<script>
import { Errors, HandlesValidationErrors } from 'laravel-nova';

class JsonForm {
    constructor() {
        this.json = {}
    }

    append(k,v) {
        this.json[k] = v
    }

    toString() {
        return JSON.stringify(this.json)
    }
}

export default {
    mixins: [HandlesValidationErrors],

    props: [
        'card',
        'errors',

        // The following props are only available on resource detail cards...
        // 'resource',
        // 'resourceId',
        // 'resourceName',
    ],

    data: () => ({
        working: false,
        resource: 'settings',
        errorData: [],
        theTab: null,
        dirty: false
    }),

    mounted() {
        this.errorData = this.errors;

        this.switchTab(this.card.fields[0])
    },

    computed: {
        cardName() {
            if (this.card.name) {
                return this.card.name
            }

            return this.__('Settings')
        },
        fields() {
            return this.theTab && this.theTab.fields
        }
    },

    methods: {
        selectTab(tab) {
            const { theTab, dirty, switchTab, fields } = this
            if (!theTab || tab.key !== theTab.key) {
                if (dirty) {
                    const newJson = new JsonForm()
                    fields.forEach(f => f.fill(newJson))
                    const testJson = newJson.toString()
                    if (testJson !== dirty) {

                        return this.$toasted.show('There are unsaved changes, are you sure you want to switch tabs?', {
                            type: 'info',
                            action : [{
                                text : 'Cancel',
                                onClick(e, toastObject) {
                                    toastObject.goAway(0);
                                }
                            }, {
                                text : 'Yes switch!',
                                onClick(e, toastObject) {
                                    toastObject.goAway(0);
                                    switchTab(tab)
                                }
                            }]
                        })
                    }
                }

                this.switchTab(tab)
            }
        },

        switchTab(tab) {
            this.theTab = tab;

            this.$nextTick(() => {
                const json = new JsonForm()
                this.fields.forEach(f => f.fill(json))
                this.dirty = json.toString()
            })
        },

        saveSettings() {
            let data = this.actionFormData()

            Nova.request()
                .post('/nova-vendor/settings-card/save-settings', data)
                .then(response => {
                    this.dirty = false

                    this.$toasted.show(this.__('Settings saved!'), {
                        type: 'success',
                        action : [{
                            text : 'Reload Page',
                            onClick(e, toastObject) {
                                toastObject.goAway(0);
                                location.reload()
                            }
                        }]
                    });

                    this.theTab.fields.forEach(f => {
                        f.fill({
                            append(k, v) {
                                f.value = v
                            }
                        })
                    })

                    this.working = false
                })
                .catch(error => {
                    this.working = false
                    if (error.response.status == 422) {
                        this.errorData = new Errors(error.response.data.errors)
                    }
                });
        },

        /**
         * Gather the action FormData for the given action.
         */
        actionFormData() {
            const dataForm = new FormData()
            this.theTab.fields.forEach(f => f.fill(dataForm))

            dataForm.append('disks', JSON.stringify(this.card.disks));

            return dataForm;
        },
    }
}
</script>
<style lang="scss">
  .tabs::-webkit-scrollbar {
    height: 8px;
    border-radius: 4px;
  }
  .tabs::-webkit-scrollbar-thumb {
    background: #cacaca;
  }
  .tabs {
    white-space: nowrap;
    margin-bottom: -2px;
  }
  .card {
    box-shadow: none;
  }
  h1 {
    display: none;
  }
  .tab {
    padding-top: 1.25rem;
    padding-bottom: 1.25rem;
  }
  .default-search > div > .relative > .flex {
    justify-content: flex-end;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    margin-top: 0.75rem;
    margin-bottom: 0;
    > .mb-6 {
      margin-bottom: 0;
    }
  }
  .tab-content > div > .relative > .flex {
    justify-content: flex-end;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    position: absolute;
    top: 0;
    right: 0;
    transform: translateY(-100%);
    align-items: center;
    height: 62px;
    z-index: 1;
    > .mb-6 {
      margin-bottom: 0;
    }
    > .w-full {
      width: auto;
      margin-left: 1.5rem;
    }
  }
</style>
