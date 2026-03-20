<template>
  <div class="app-container">
    <nav class="navbar">
      <div class="navbar-inner">
        <router-link to="/" class="navbar-brand">
          Schützen<span>wettbewerb</span>
        </router-link>
        <ul class="navbar-links">
          <li><router-link to="/events">Events</router-link></li>
          <li><router-link to="/shooters">Schützen</router-link></li>
          <li><router-link to="/registration">Anmeldung</router-link></li>
          <li><router-link to="/reports">Auswertung</router-link></li>
          <li><router-link to="/age-groups">Altersklassen</router-link></li>
        </ul>
      </div>
    </nav>
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
</script>
