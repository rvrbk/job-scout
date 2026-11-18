<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store as signUp } from '@/routes/register';
import { register } from '@/routes/invitations';

defineOptions({
    layout: {
        title: 'Create your account',
        description: 'Sign up for Job Scout',
    },
});

const props = defineProps<{
    token?: string;
    inviter?: string;
    passwordRules: string;
}>();

const formAction = props.token ? register.form(props.token) : signUp.form();
</script>

<template>
    <Head :title="inviter ? 'Accept invitation' : 'Sign up'" />

    <p
        v-if="inviter"
        class="mb-6 text-center text-sm text-muted-foreground"
    >
        {{ inviter }} invited you. Choose a password to finish signing up.
    </p>

    <Form
        v-bind="formAction"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    v-focus
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :disabled="processing"
                data-test="register-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>

            <p
                v-if="!inviter"
                class="text-center text-sm text-muted-foreground"
            >
                Already have an account?
                <Link :href="login()" class="underline">Log in</Link>
            </p>
        </div>
    </Form>
</template>
