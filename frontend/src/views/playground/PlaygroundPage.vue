<template>

  <PageContainer>

    <h1 class="mb-5">
      Design System Playground
    </h1>

    <InputPlayground />

    <PasswordPlayground />

  </PageContainer>

</template>

<script setup>
import PageContainer from '@/components/layout/PageContainer.vue'
import PasswordPlayground from './forms/PasswordPlayground.vue'

import InputPlayground from './forms/InputPlayground.vue'
//import TextareaPlayground from './forms/TextareaPlayground.vue'
//import SelectPlayground from './forms/SelectPlayground.vue'
//import CheckboxPlayground from './forms/CheckboxPlayground.vue'
//import RadioPlayground from './forms/RadioPlayground.vue'
</script>