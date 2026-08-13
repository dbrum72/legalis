<template>
    <Teleport to="body">
        <div v-if="open" class="app-confirm-dialog" role="presentation" @click.self="handleCancel">
            <section class="app-confirm-dialog__panel" role="dialog" aria-modal="true" :aria-labelledby="titleId"
                :aria-describedby="messageId">
                <header class="app-confirm-dialog__header">
                    <h2 :id="titleId" class="app-confirm-dialog__title">
                        {{ title }}
                    </h2>
                </header>

                <div class="app-confirm-dialog__body">
                    <p :id="messageId" class="app-confirm-dialog__message">
                        {{ message }}
                    </p>
                </div>

                <footer class="app-confirm-dialog__actions">
                    <AppButton type="button" variant="ghost" :disabled="loading" @click="handleCancel">
                        {{ cancelLabel }}
                    </AppButton>

                    <AppButton type="button" variant="accent" :loading="loading" :disabled="loading"
                        @click="handleConfirm">
                        {{ confirmLabel }}
                    </AppButton>
                </footer>
            </section>
        </div>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue'

import AppButton from '@/components/ui/AppButton/index.vue'

import { appConfirmDialogProps } from './props.js'

const props = defineProps(
    appConfirmDialogProps,
)

const emit = defineEmits([
    'confirm',
    'cancel',
])

const titleId = computed(() =>
    'app-confirm-dialog-title',
)

const messageId = computed(() =>
    'app-confirm-dialog-message',
)

function handleConfirm() {
    if (props.loading) {
        return
    }

    emit('confirm')
}

function handleCancel() {
    if (props.loading) {
        return
    }

    emit('cancel')
}
</script>

<style src="./style.css"></style>