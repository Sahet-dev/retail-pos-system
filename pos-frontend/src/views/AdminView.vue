<script setup>
import { ref, onMounted } from 'vue'

const sales = ref([])
const stockEvents = ref([])
const locationId = 1 // the location you want to monitor
import Echo from 'laravel-echo'
import Pusher from "pusher-js";

onMounted(() => {
    const echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: '127.0.0.1',
        wsPort: 8080,
        forceTLS: false,
        enabledTransports: ['ws'],
        client: Pusher,
    })

    echo.channel(`location.${locationId}`)
        .subscribed(() => {
            console.log('✅ Subscribed to location.' + locationId)
        })
        .listen('.sale.completed', e => {
            console.log('📦 Sale event received', e)
        })

        .listen('.stock.updated', e => {
            stockEvents.value.unshift(e)
        })

})


</script>

<template>
    <div class="admin">
        <h1>Manager Dashboard</h1>

        <section>
            <h2>Live Sales</h2>
            <div v-for="s in sales" :key="s.id" class="card">
                Sale #{{ s.id }} — {{ s.total }}
            </div>
        </section>

        <section>
            <h2>Stock Receiving</h2>
            <div v-for="(e, i) in stockEvents" :key="i" class="card">
                Product {{ e.product_id }} +{{ e.quantity }}
            </div>
        </section>
    </div>
</template>

<style scoped>
.admin {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
}

h1 {
    font-size: 32px;
    margin-bottom: 20px;
}

section {
    margin-bottom: 40px;
}

.card {
    padding: 14px 18px;
    background: #fff;
    border-radius: 12px;
    margin-bottom: 10px;
    box-shadow: 0 10px 20px rgba(0,0,0,.06);
}
</style>
