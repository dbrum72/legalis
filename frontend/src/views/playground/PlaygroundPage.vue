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

    <UrlPlayground />

    <NumberPlayground />

    <CurrencyPlayground />

    <TextareaPlayground />

    <SelectPlayground />

    <AutocompletePlayground />

    <CheckboxPlayground />

    <CheckboxGroupPlayground />

    <RadioPlayground />

    <RadioGroupPlayground />
    
    <SwitchPlayground />

  </PageContainer>

</template>

<script setup>
import InputPlayground from './forms/InputPlayground.vue'
import PageContainer from '@/components/layout/PageContainer.vue'

import PasswordPlayground from './forms/PasswordPlayground.vue'
import SearchPlayground from './forms/SearchPlayground.vue'
import EmailPlayground from './forms/EmailPlayground.vue'
import PhonePlayground from './forms/PhonePlayground.vue'
import UrlPlayground from './forms/UrlPlayground.vue'
import NumberPlayground from './forms/NumberPlayground.vue'
import CurrencyPlayground from './forms/CurrencyPlayground.vue'
import TextareaPlayground from './forms/TextareaPlayground.vue'
import SelectPlayground from './forms/SelectPlayground.vue'
import AutocompletePlayground from './forms/AutocompletePlayground.vue'
import RadioPlayground from './forms/RadioPlayground.vue'
import CheckboxPlayground from './forms/CheckboxPlayground.vue'
import SwitchPlayground from './forms/SwitchPlayground.vue'
import RadioGroupPlayground from './forms/RadioGroupPlayground.vue'
import CheckboxGroupPlayground from './forms/CheckboxGroupPlayground.vue'


</script>