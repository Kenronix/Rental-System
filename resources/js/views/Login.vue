<script setup>
import landingPageImage from '../images/landingPage.png'
import tahananLogo from '../images/tahananLogo.png'
import tahananLogoName from '../images/tahananLogoName.png'
</script>

<template>
  <div class="login-container" :style="{ backgroundImage: `url(${landingPageImage})` }">
    <div class="logo-container">
      <img :src="tahananLogo" alt="Tahanan Logo" class="tahanan-logo">
      <img :src="tahananLogoName" alt="Tahanan Logo Name" class="tahanan-logo-name">
    </div>
    <div class="login-content-text">
        <p>Manage Your <span class="gradient-text">Rental Properties</span></p>
        <p>With Ease and Confidence</p>
    </div>
  </div>
</template>


<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

.login-container {
  width: 100vw;
  height: 100vh;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  position: relative;
}

.logo-container {
  position: absolute;
  top: 0;
  left: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  z-index: 10;
}

.tahanan-logo {
  width: 80px;
  height: auto;
  padding: 10px;
  margin-left: 20px;
  margin-top: 20px;
}

.tahanan-logo-name {
  width: auto;
  height: auto;
  max-height: 50px;
  margin-top: 30px;
}

.login-content-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  font-family: 'Montserrat', sans-serif;
  color: white;
  white-space: nowrap;
}

.login-content-text p {
  margin: 0;
  font-weight: bold;
  font-size: 70px;

}

.login-content-text p:first-child::after {
  content: ' ';
}

.gradient-text {
  background: linear-gradient(to right, #1500FF, #050090);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}   
</style>
