# Forms API

## Objetivo

Padronizar a API pública dos componentes de formulário do Design System.

Todos os componentes devem seguir esta especificação.

---

# Convenções Gerais

## v-model

Todos os componentes que representam um valor devem suportar:

```vue
v-model="value"
```

emitindo:

```
update:modelValue
```

---

## Eventos

Todos os controles devem emitir:

```
focus
blur
```

---

## Estados

Sempre utilizar:

```
disabled
required
```

Utilizar:

```
readonly
```

apenas quando o elemento HTML possuir suporte nativo.

---

## Acessibilidade

Todos os controles devem suportar:

```
aria-invalid
aria-describedby
```

Campos tradicionais (`Input`, `Textarea`, `Select`) recebem esses atributos através do `AppField`.

Controles de seleção (`Checkbox`, `Radio`, `Switch`) recebem explicitamente.

---

# AppInput

## Props

| Prop | Tipo |
|------|------|
| modelValue | String |
| label | String |
| hint | String |
| error | String |
| placeholder | String |
| disabled | Boolean |
| readonly | Boolean |
| required | Boolean |
| autofocus | Boolean |
| autocomplete | String |
| maxlength | Number |
| minlength | Number |
| inputmode | String |
| id | String |
| name | String |

## Eventos

```
update:modelValue
focus
blur
```

---

# AppTextarea

## Props

| Prop | Tipo |
|------|------|
| modelValue | String |
| label | String |
| hint | String |
| error | String |
| placeholder | String |
| rows | Number |
| cols | Number |
| wrap | String |
| disabled | Boolean |
| readonly | Boolean |
| required | Boolean |
| autofocus | Boolean |
| autocomplete | String |
| maxlength | Number |
| minlength | Number |
| id | String |
| name | String |

## Eventos

```
update:modelValue
focus
blur
```

---

# AppSelect

## Props

| Prop | Tipo |
|------|------|
| modelValue | String \| Number \| Boolean \| null |
| label | String |
| hint | String |
| error | String |
| placeholder | String |
| options | Array |
| optionLabel | String |
| optionValue | String |
| disabled | Boolean |
| required | Boolean |
| autofocus | Boolean |
| id | String |
| name | String |

## Eventos

```
update:modelValue
focus
blur
```

---

# AppCheckbox

## Props

| Prop | Tipo |
|------|------|
| modelValue | Boolean |
| label | String |
| hint | String |
| error | String |
| disabled | Boolean |
| required | Boolean |
| autofocus | Boolean |
| indeterminate | Boolean |
| id | String |
| name | String |

## Eventos

```
update:modelValue
focus
blur
```

---

# AppRadio

## Props

| Prop | Tipo |
|------|------|
| modelValue | String \| Number \| Boolean \| null |
| value | String \| Number \| Boolean |
| label | String |
| hint | String |
| error | String |
| disabled | Boolean |
| required | Boolean |
| autofocus | Boolean |
| id | String |
| name | String |

## Eventos

```
update:modelValue
focus
blur
```

---

# Convenções de Componentes

Todo componente público possui:

```
index.vue
props.js
style.css
README.md
```

Todo controle interno possui:

```
index.vue
props.js
style.css
README.md
```

---

# Estrutura

```
AppInput
    ↓
InputControl
        ↓
<input>

AppTextarea
    ↓
TextareaControl
        ↓
<textarea>

AppSelect
    ↓
SelectControl
        ↓
<select>

AppCheckbox
    ↓
CheckboxControl
        ↓
<input type="checkbox">

AppRadio
    ↓
RadioControl
        ↓
<input type="radio">
```

---

# Princípios

- API pública estável.
- Controles internos não fazem parte da API pública.
- Sem barrels internos.
- Sem abstrações prematuras.
- CSS isolado por componente.
- Design Tokens para dimensões, cores e espaçamentos.
- Props compartilhadas apenas quando agregam valor.
- Componentes públicos não conhecem HTML nativo.