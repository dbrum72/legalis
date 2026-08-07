<template>
  <PageContainer>
    <AppInput v-model="name" id="name" label="Nome" required hint="Informe o nome completo."
      placeholder="Digite seu nome" />

    <hr />

    <AppTextarea v-model="description" id="description" label="Descrição" hint="Descreva o ocorrido." :rows="5"
      placeholder="Digite a descrição..." />

    <hr />

    <AppSelect v-model="profile" id="profile" label="Perfil" required placeholder="Selecione um perfil"
      :options="profiles" />

    <hr />

    <AppCheckbox v-model="accepted" id="accepted" name="accepted" label="Aceito os termos de uso"
      hint="Você deve aceitar para continuar." required />

    <hr />

    <RadioGroup v-model="gender" id="gender" name="gender" label="Sexo" hint="Selecione uma opção."
      :options="genderOptions" />

    <p>Selecionado: {{ gender }}</p>

    <hr />

    <AppSwitch v-model="notifications" id="notifications" name="notifications" label="Receber notificações"
      hint="Permite o envio de alertas do sistema." />

    <hr />

    <p>Nome: {{ name }}</p>
    <p>Descrição: {{ description }}</p>
    <p>Perfil: {{ profile }}</p>
  </PageContainer>
</template>

<script setup>
import { ref } from 'vue'

import {
  AppInput,
  AppTextarea,
  AppSelect,
  AppCheckbox,
  RadioGroup,
  AppSwitch,
} from '@/components/forms'

const name = ref('')
const description = ref('')
const profile = ref('')
const accepted = ref(false)
const gender = ref('M')
const notifications = ref(false)

const profiles = [
  {
    label: 'Administrador',
    value: 'admin',
  },
  {
    label: 'Advogado',
    value: 'lawyer',
  },
  {
    label: 'Assistente',
    value: 'assistant',
  },
]
</script>