<script setup>
const props = defineProps({
  variant: {
    type: String,
    default: 'center', 
    validator: (value) => ['center'].includes(value)
  },
  showArrow: {
    type: Boolean,
    default: false
  },
  text: {
    type: String,
    default: 'Login'
  }
})

const emit = defineEmits(['click'])

const handleClick = () => {
  emit('click')
}
</script>

<template>
  <button 
    :class="['login-button', `login-button--${variant}`]"
    @click="handleClick"
  >
    {{ text }}<span v-if="showArrow"> →</span>
  </button>
</template>
<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.login-button {
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.3s ease;
  z-index: 10;
}

/* Center Button Style */
.login-button--center {
  margin-top: 40px;
  background-color: #1500FF;
  color: white;
  padding: 20px 64px;
  border-radius: 999px;
  font-size: 20px;
  box-shadow: 0 4px 12px rgba(21, 0, 255, 0.3);
  display: inline-block;
}

.login-button--center:hover {
  background-color: #0f00cc;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(21, 0, 255, 0.4);
}

.login-button--center:active {
  transform: translateY(0);
}
</style>

