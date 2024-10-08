<script setup>
import { computed, ref, toRefs } from "vue";
import useVuelidate from "@vuelidate/core";
import {
    required,
    requiredIf,
    email,
    minLength,
    maxLength,
} from "../../../utils/i18n-validators";

import { useI18n } from "vue-i18n";
import { useStore } from "vuex";
import { useRouter, useRoute } from "vue-router";

import {
    CardHeader,
    BaseInput,
    CardUpload,
    CardSectionWithHeader,
    CardContacts,
    CardLocations,
    CardRemarks,
    SelectInput
} from "../../../components/import";
import { icTypes } from "../../../utils/constraints";

const { t } = useI18n();
const router = useRouter();
const route = useRoute();

const props = defineProps({
    formData: {
        type: Object,
        required: true,
        default: () => {},
    },
});
const { formData } = toRefs(props);

const rules = computed(() => ({
    fullname: { required, minLength: minLength(8), maxLength: maxLength(100) },
    company_name: {
        requiredIfRef: requiredIf(formData.value.type === 2),
        minLength: minLength(8),
        maxLength: maxLength(100),
    },
    email: { email },
    is_active: { required },
    type: { required },
}));

const $v = useVuelidate(rules, formData);
const store = useStore();

const onSubmit = async () => {
    const validate = await $v.value.$validate();
    if (!validate) {
        return;
    }

    if (route.params.id) {
        await store.dispatch("supplier/updateSupplier", formData.value);
        router.push({ name: "supplier.list" });
    }

    if (!route.params.id) {
        await store.dispatch("supplier/createSupplier", formData.value);
        router.push({ name: "supplier.list" });
    }
};

const clearCompanyName = () => {
    if (formData.value.type && formData.value.type === 1) {
        formData.value.company_name = null;
    }
};
</script>

<template>
    <q-card
        class="bg-transparent"
        flat
        style="
            max-width: 70rem;
            margin-left: auto;
            margin-right: auto;
            border-radius: 20px;
        "
    >
        <CardHeader
            @on-submit="onSubmit"
            :reference="formData?.fullname"
            :item-id="formData?.id"
            :class="!$q.dark.isActive ? 'bg-white' : 'bg-dark'"
        />
        <q-form @submit="onSubmit">
            <div class="row q-col-gutter-md q-mt-xs">
                <!-- Image -->
                <CardUpload
                    :form-data="formData"
                    :config="{ keyOfImages: 'avatar' }"
                />
                <!-- General Informations -->
                <CardSectionWithHeader title="card.general_info">
                    <div
                        class="row justify-between q-py-lg"
                        :class="!$q.dark.isActive ? 'bg-white' : 'bg-dark'"
                    >
                        <div
                            class="col-lg-6 col-md-6 col-xs-12 q-px-md q-pb-sm"
                        >
                            <BaseInput
                                v-model="formData.fullname"
                                :label="t('fullname')"
                                :error="$v.fullname.$error"
                                @input="() => $v.fullname.$touch()"
                                @blur="() => $v.fullname.$touch()"
                                :errors="$v.fullname.$errors"
                            />
                        </div>

                        <div
                            class="col-lg-6 col-md-6 col-xs-12 q-px-md q-pb-sm"
                        >
                            <SelectInput
                                v-model="formData.type"
                                @update:modelValue="clearCompanyName"
                                :label="t('type')"
                                :options="icTypes"
                                :error="$v.type.$error"
                                :errors="$v.type.$errors"
                                @input="() => $v.type.$touch()"
                                @blur="() => $v.type.$touch()"
                            />
                        </div>

                        <div
                            class="col-lg-6 col-md-6 col-xs-12 q-px-md q-pb-sm"
                            v-if="formData.type === 2"
                        >
                            <BaseInput
                                v-model="formData.company_name"
                                :label="t('company_name')"
                                :error="$v.company_name.$error"
                                @input="() => $v.company_name.$touch()"
                                @blur="() => $v.company_name.$touch()"
                                :errors="$v.company_name.$errors"
                            />
                        </div>

                        <div
                            class="col-lg-6 col-md-6 col-xs-12 q-px-md q-pb-sm"
                        >
                            <BaseInput
                                v-model="formData.email"
                                :label="t('email')"
                                type="email"
                                :error="$v.email.$error"
                                @input="() => $v.email.$touch()"
                                @blur="() => $v.email.$touch()"
                                :errors="$v.email.$errors"
                            />
                        </div>

                        <div
                            class="col-lg-6 col-md-6 col-xs-12 q-px-md q-pb-sm"
                        >
                            <q-toggle
                                keep-color
                                v-model="formData.is_active"
                                :trueValue="1"
                                :falseValue="0"
                                checked-icon="fa-solid fa-check"
                            unchecked-icon="fa-solid fa-xmark"
                                :label="t('is_active')"
                                :error="$v.is_active.$error"
                                :errors="$v.is_active.$errors"
                                @input="() => $v.is_active.$touch()"
                                @blur="() => $v.is_active.$touch()"
                            />
                        </div>
                    </div>
                </CardSectionWithHeader>

                <!-- Contacts -->
                <CardContacts :form-data="formData" />

                <!-- Locations -->
                <CardLocations :form-data="formData" />

                <!-- Remarks -->
                <CardRemarks :form-data="formData" />
            </div>
        </q-form>
    </q-card>
</template>
