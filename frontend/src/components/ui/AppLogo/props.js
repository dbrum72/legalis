export const appLogoProps = {
  text: {
    type: String,
    default: 'Legalis',
  },

  to: {
    type: [String, Object],
    default: () => ({
      name: 'dashboard',
    }),
  },

  ariaLabel: {
    type: String,
    default: 'Legalis — ir para o início',
  },
}