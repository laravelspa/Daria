<script setup>
import { defineAsyncComponent, reactive } from "vue";
import { useStore } from "vuex";
import { TheSpinner } from "../../../components/import";
const Form = defineAsyncComponent(() => import("../components/Form.vue"));

const formData = reactive({
    username: "",
    email: "",
    firstname: "",
    lastname: "",
    password: "",
    password_confirmation: "",
    date_of_birth: null,
    date_of_joining: null,
    gender: 1,
    is_active: 0,
    send_notify: false,
    role_ids: [],
    permissions: [],
    contacts: [],
    locations: [],
    remarks: ""
});

const store = useStore();

await Promise.all([
    store.dispatch("permission/fetchOptions"),
    store.dispatch("role/fetchOptions"),
]);
</script>

<template>
    <Suspense>
        <template #default>
            <Form :form-data="formData" />
        </template>
        <template #fallback>
            <div class="fixed-center">
                <the-spinner />
            </div>
        </template>
    </Suspense>
</template>
