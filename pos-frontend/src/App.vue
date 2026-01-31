<script setup>
import { ref, onMounted } from 'vue'
import apiClient from './api/apiClient'

const barcode = ref('')
const items = ref([])
const total = ref(0)
const cashGiven = ref('')
const locationId = 1

const lastSaleId = ref(null)

const scan = async () => {
    if (!barcode.value) return

    try {
        const res = await apiClient.post('/api/scan', {
            barcode: barcode.value,
            location_id: locationId,
            sale_id: lastSaleId.value
        })

        const { product, quantity, sale_total, sale_id } = res.data
        lastSaleId.value = sale_id  // now it works!

        const existing = items.value.find(i => i.name === product)
        if (existing) {
            existing.quantity = quantity
        } else {
            items.value.push({ name: product, quantity })
        }

        total.value = sale_total
        barcode.value = ''
    } catch (e) {
        alert(e.response?.data?.message || 'Scan failed')
    }
}


const cash = async () => {
    const amount = Number(cashGiven.value);
    if (!amount || amount < total.value) {
        alert('Insufficient cash');
        return;
    }

    if (!lastSaleId.value) {
        alert('No sale found to complete');
        return;
    }

    try {
        const res = await apiClient.post(`/api/sales/cash/${lastSaleId.value}`, {
            cash_given: amount
        });

        const { change_amount, total: saleTotal } = res.data;
        console.log('Response data:', res.data);

        alert(`Sale completed!\nTotal: ${saleTotal}\nChange: ${change_amount}`);

        resetPOS();
        lastSaleId.value = null;
    } catch (e) {
        console.error(e);
        alert(e.response?.data?.message || 'Payment failed');
    }
};




const resetPOS = () => {
    items.value = []
    total.value = 0
    cashGiven.value = ''
}

onMounted(() => {
    document.getElementById('barcode')?.focus()
})
</script>

<template>
    <div class="pos">
        <h1 class="title">Point of Sale</h1>

        <input
            id="barcode"
            class="input barcode"
            v-model="barcode"
            @keyup.enter="scan"
            placeholder="Scan barcode"
        />

        <div class="items">
            <div
                class="item"
                v-for="item in items"
                :key="item.name"
            >
                <span class="name">{{ item.name }}</span>
                <span class="qty">× {{ item.quantity }}</span>
            </div>
        </div>

        <div class="total">
            <span>TOTAL</span>
            <strong>{{ total }}</strong>
        </div>

        <input
            type="number"
            class="input cash"
            v-model="cashGiven"
            placeholder="Cash given"
        />

        <button class="cash-btn" @click="cash">
            CASH
        </button>
    </div>
</template>

<style scoped>
.pos {
    max-width: 520px;
    margin: 60px auto;
    padding: 32px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
    font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
}

.title {
    text-align: center;
    font-size: 32px;
    letter-spacing: 1px;
    margin-bottom: 28px;
    font-weight: 600;
}

.input {
    width: 100%;
    font-size: 22px;
    padding: 16px 18px;
    margin-bottom: 20px;
    border-radius: 12px;
    border: 1px solid #ddd;
    outline: none;
}

.input:focus {
    border-color: #000;
}

.barcode {
    font-size: 26px;
    font-weight: 500;
}

.items {
    margin: 20px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.item {
    display: flex;
    justify-content: space-between;
    padding: 14px 4px;
    font-size: 20px;
}

.name {
    font-weight: 500;
}

.qty {
    opacity: 0.7;
}

.total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 28px;
    margin: 28px 0;
}

.total strong {
    font-size: 36px;
}

.cash {
    font-size: 24px;
}

.cash-btn {
    width: 100%;
    padding: 20px;
    font-size: 26px;
    font-weight: 600;
    border-radius: 14px;
    border: none;
    background: #000;
    color: #fff;
    cursor: pointer;
}

.cash-btn:hover {
    background: #222;
}
</style>
