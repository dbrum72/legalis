<template>

  <PageContainer>

    <h1 class="mb-5">
      Design System Playground
    </h1>

    <ButtonPlayground />

    <CardPlayground />

    <InputPlayground />

    <PasswordPlayground />

    <SearchPlayground />

    <EmailPlayground />

    <PhonePlayground />

    <UrlPlayground />

    <NumberPlayground />

    <CurrencyPlayground />

    <DatePlayground />

    <FileUploadPlayground />

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
import { PageContainer } from '@/components/layout'

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
import DatePlayground from './forms/DatePlayground.vue'
import FileUploadPlayground from './forms/FileUploadPlayground.vue'
import ButtonPlayground from './ui/ButtonPlayground.vue'
import CardPlayground from './ui/CardPlayground.vue'


</script>