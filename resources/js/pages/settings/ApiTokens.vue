<script setup lang="ts">
import { Form, Head } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import Heading from "@/components/Heading.vue";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { destroy, index, store } from "@/routes/api-tokens";

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: "AI assistants",
                href: index(),
            },
        ],
    },
});

const props = defineProps<{
    tokens: {
        id: number;
        name: string;
        last_used_at: string | null;
        created_at: string;
    }[];
    mcpUrl: string;
    newToken: string | null;
}>();

const copied = ref<string | null>(null);

const clients = computed(() => {
    const url = props.mcpUrl;
    const header = `Bearer ${props.newToken ?? "<your token>"}`;
    const json = (data: object) => JSON.stringify(data, null, 2);

    return [
        {
            name: "Claude Code",
            where: "Run in a terminal.",
            code: `claude mcp add --transport http job-scout ${url} --header "Authorization: ${header}"`,
        },
        {
            name: "VS Code (GitHub Copilot)",
            where: 'Save as .vscode/mcp.json in your project, or run "MCP: Open User Configuration" for all projects. Then start the server from the file and use Copilot Chat in Agent mode.',
            code: json({
                servers: {
                    "job-scout": {
                        type: "http",
                        url,
                        headers: { Authorization: header },
                    },
                },
            }),
        },
        {
            name: "Copilot CLI",
            where: "Add to ~/.copilot/mcp-config.json, or use /mcp add inside the CLI.",
            code: json({
                mcpServers: {
                    "job-scout": {
                        type: "http",
                        url,
                        headers: { Authorization: header },
                        tools: ["*"],
                    },
                },
            }),
        },
        {
            name: "Cursor",
            where: "Add to ~/.cursor/mcp.json (or .cursor/mcp.json in a project).",
            code: json({
                mcpServers: {
                    "job-scout": { url, headers: { Authorization: header } },
                },
            }),
        },
        {
            name: "Gemini CLI",
            where: "Run in a terminal.",
            code: `gemini mcp add --transport http job-scout ${url} --header "Authorization: ${header}"`,
        },
        {
            name: "Codex",
            where: "Add to ~/.codex/config.toml.",
            code: `[mcp_servers.job-scout]\nurl = "${url}"\nhttp_headers = { Authorization = "${header}" }`,
        },
    ];
});

async function copy(name: string, value: string) {
    try {
        await navigator.clipboard.writeText(value);
        copied.value = name;
        setTimeout(() => (copied.value = null), 2000);
    } catch {
        // Clipboard unavailable; the value stays selectable in the field.
    }
}

const date = (value: string) => new Date(value).toLocaleDateString();
</script>

<template>
    <Head title="AI assistants" />

    <h1 class="sr-only">AI assistants</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="AI assistants"
            description="Let your coding assistant add facts about your projects to your ledger. Everything it adds stays unconfirmed until you confirm it."
        />

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
            class="space-y-2"
        >
            <Label for="name">Token name</Label>
            <div class="flex gap-2">
                <Input
                    id="name"
                    name="name"
                    placeholder="Claude Code on my laptop"
                    required
                />
                <Button :disabled="processing" data-test="create-token-button">
                    Create token
                </Button>
            </div>
            <InputError :message="errors.name" />
        </Form>

        <div v-if="newToken" class="space-y-2 rounded-lg border p-3 text-sm">
            <p class="font-medium">
                Copy your token now. It is shown only once.
            </p>
            <input
                readonly
                :value="newToken"
                class="w-full rounded-md border bg-muted px-2 py-1 font-mono text-xs"
                @focus="($event.target as HTMLInputElement).select()"
            />
        </div>

        <p v-else class="text-sm text-muted-foreground">
            MCP server address:
            <span class="font-mono">{{ mcpUrl }}</span>
        </p>

        <div class="space-y-2">
            <h2 class="text-sm font-medium">Connect an assistant</h2>
            <p class="text-sm text-muted-foreground">
                Any MCP client that supports HTTP servers with a custom header
                works: use the address above and an Authorization: Bearer
                header. Setup for common ones{{
                    newToken
                        ? ", with your new token filled in"
                        : " (create a token first to get it filled in)"
                }}:
            </p>
            <details
                v-for="client in clients"
                :key="client.name"
                class="rounded-lg border text-sm"
            >
                <summary class="cursor-pointer px-3 py-2">
                    {{ client.name }}
                </summary>
                <div class="space-y-2 px-3 pb-3">
                    <p class="text-muted-foreground">{{ client.where }}</p>
                    <textarea
                        readonly
                        rows="5"
                        :value="client.code"
                        class="w-full rounded-md border bg-muted px-2 py-1 font-mono text-xs"
                        @focus="($event.target as HTMLTextAreaElement).select()"
                    />
                    <Button
                        size="sm"
                        variant="outline"
                        @click="copy(client.name, client.code)"
                    >
                        {{ copied === client.name ? "Copied" : "Copy" }}
                    </Button>
                </div>
            </details>
        </div>

        <ul v-if="tokens.length" class="space-y-3">
            <li
                v-for="token in tokens"
                :key="token.id"
                class="flex items-center justify-between gap-2 rounded-lg border p-3 text-sm"
            >
                <div>
                    <p class="font-medium">{{ token.name }}</p>
                    <p class="text-muted-foreground">
                        Created {{ date(token.created_at) }} ·
                        {{
                            token.last_used_at
                                ? `last used ${date(token.last_used_at)}`
                                : "never used"
                        }}
                    </p>
                </div>
                <Form v-bind="destroy.form(token.id)">
                    <Button size="sm" variant="ghost" type="submit">
                        Revoke
                    </Button>
                </Form>
            </li>
        </ul>
    </div>
</template>
