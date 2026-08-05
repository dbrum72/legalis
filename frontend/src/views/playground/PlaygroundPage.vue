<template>

  <PageContainer>

    <h1 class="mb-5">
      Design System Playground
    </h1>

    <InputPlayground />

    <PasswordPlayground />

    <SearchPlayground />

    <EmailPlayground />

    <PhonePlayground />

  </PageContainer>

</template>

<script setup>
import InputPlayground from './forms/InputPlayground.vue'
import PageContainer from '@/components/layout/PageContainer.vue'

import PasswordPlayground from './forms/PasswordPlayground.vue'
import SearchPlayground from './forms/SearchPlayground.vue'
import EmailPlayground from './forms/EmailPlayground.vue'
import PhonePlayground from './forms/PhonePlayground.vue'
</script>