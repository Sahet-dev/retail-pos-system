<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Echo from 'laravel-echo'
import Kpi from "@/Components/Kpi.vue";

const page = usePage()

const tabs = [
    { key: 'today', label: 'Today' },
    { key: 'live', label: 'Live Sales' },
    { key: 'stock', label: 'Stock Alerts 🚨' },
    { key: 'top', label: 'Top Products 🔥' },
    { key: 'open', label: 'Open Sales 🕒' },
    { key: 'cash', label: 'Cash 💵' },
]

// Reactive states
const liveSales = ref(page.props.liveFeed ?? [])
const stockAlerts = ref(page.props.stockAlerts ?? [])
const topProducts = ref(page.props.topProducts ?? { byQty: [], byRevenue: [] })
const openSales = ref(page.props.openSales ?? [])
const sales = ref(page.props.initialSales ?? [])
const stockEvents = ref(page.props.initialStockEvents ?? [])

const today = ref({
    salesTotal: Number(page.props.todayStats?.salesTotal ?? 0),
    transactions: Number(page.props.todayStats?.transactions ?? 0),
    itemsSold: Number(page.props.todayStats?.itemsSold ?? 0),
    cash: Number(page.props.todayStats?.cash ?? 0),
})




const cash = ref(page.props.cashStats ?? {
    expected: 0,
    salesCount: 0,
    avgTicket: 0,
})

const locationId = 1
let echo

onMounted(() => {
    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? '127.0.0.1',
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        forceTLS: false,
        enabledTransports: ['ws'],
        withCredentials: true,
    })

    const channel = echo.private(`location.${locationId}`)

    // Product scanned -> add to live feed
    channel.listen('.product.scanned', e => {
        liveSales.value.unshift(e)
        if (liveSales.value.length > 50) liveSales.value.pop()
    })

    // Sale completed -> update live feed + KPIs
    channel.listen('.sale.completed', e => {

        today.value = {
            salesTotal: Number(e.today.salesTotal ?? 0),
            transactions: Number(e.today.transactions ?? 0),
            itemsSold: Number(e.today.itemsSold ?? 0),
            cash: Number(e.today.cash ?? 0), // <-- use salesTotal if you want them equal
        }

    })





    // Sale created -> add to open sales
    channel.listen('.sale.created', e => {
        openSales.value.unshift({
            id: e.id,
            terminal: e.terminal_name ?? 'POS',
            minutes: 0,
            started_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        })
        console.log(openSales)
    })

    // Stock movement created -> add to stock events & live feed if sale type
    channel.listen('.stock.movement.created', e => {
        stockEvents.value.unshift(e)

        if (e.type === 'sale') {
            liveSales.value.unshift({
                id: e.id,
                time: e.time,
                product: e.product,
                qty: e.qty,
                price: e.price,
                kind: 'item'
            })
        }

        if (stockEvents.value.length > 50) stockEvents.value.pop()
    })

    // Stock updated -> update stock alerts
    channel.listen('.stock.updated', e => {
        const index = stockAlerts.value.findIndex(s => s.id === e.product_id)
        const msg = e.quantity < 0 ? { type: 'negative', message: `Negative stock (${e.quantity})` } :
            e.quantity === 0 ? { type: 'out', message: 'Out of stock' } :
                { type: 'low', message: `${e.quantity} left` }

        const stockObj = { id: e.product_id, name: e.product_name ?? 'Product', ...msg }

        if (index >= 0) stockAlerts.value[index] = stockObj
        else stockAlerts.value.unshift(stockObj)
    })
})

onBeforeUnmount(() => {
    if (echo) {
        echo.leave(`location.${locationId}`)
        echo.disconnect()
    }
})
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 px-4 py-6">
            <TabGroup>
                <!-- Tabs -->
                <TabList
                    class="flex flex-wrap gap-2 rounded-2xl bg-gradient-to-r from-slate-100 to-slate-200 p-2 shadow-inner"
                >
                    <Tab
                        v-for="tab in tabs"
                        :key="tab.key"
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                'relative px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200',
                'focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400',
                selected
                  ? 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg scale-[1.03]'
                  : 'text-gray-600 hover:bg-white/70 hover:shadow'
              ]"
                        >
                            {{ tab.label }}
                            <span
                                v-if="selected"
                                class="absolute inset-0 rounded-xl ring-2 ring-blue-400/30"
                            />
                        </button>
                    </Tab>
                </TabList>

                <!-- Panels -->
                <TabPanels class="mt-6">
                    <!-- Today -->
                    <TabPanel class="panel animate-fadeIn">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <Kpi title="Today’s Sales" :value="today.salesTotal" suffix="€" />
                            <Kpi title="Transactions" :value="today.transactions" />
                            <Kpi title="Items Sold" :value="today.itemsSold" />
                            <Kpi title="Cash" :value="Number(today.cash).toFixed(2)" suffix="€" />


                        </div>
                    </TabPanel>

                    <!-- Live sales -->
                    <TabPanel class="panel animate-fadeIn">
                        <ul class="space-y-3 max-h-96 overflow-y-auto">
                            <li
                                v-for="sale in liveSales"
                                :key="sale.id"
                                class="flex items-center justify-between rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-gray-100 hover:shadow transition"
                            >
                                <div>
                                    <div class="font-semibold">
                                        <template v-if="sale.kind === 'item'">
                                            {{ sale.product }} ×{{ sale.qty }}
                                        </template>
                                        <template v-else>
                                            🧾 {{ sale.product }}
                                        </template>
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ sale.time }} · {{ sale.cashier }}
                                    </div>
                                </div>
                                <div class="font-bold text-green-600">
                                    +{{ Number(sale.price).toFixed(2) }} €

                                </div>
                            </li>
                        </ul>
                    </TabPanel>

                    <!-- Stock alerts -->
                    <TabPanel class="panel animate-fadeIn">
                        <ul class="space-y-3">
                            <li
                                v-for="item in stockAlerts"
                                :key="item.id"
                                :class="[
                  'flex items-center justify-between rounded-xl px-4 py-3 shadow-sm ring-1',
                  item.type === 'low'
                    ? 'bg-yellow-50 ring-yellow-200 text-yellow-900'
                    : 'bg-red-50 ring-red-200 text-red-900'
                ]"
                            >
                <span class="font-semibold flex items-center gap-2">
                  {{ item.type === 'low' ? '⚠️' : '⛔' }} {{ item.name }}
                </span>
                                <span class="text-sm opacity-80">
                  {{ item.message }}
                </span>
                            </li>
                        </ul>
                    </TabPanel>

                    <!-- Top products -->
                    <TabPanel class="panel animate-fadeIn">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-semibold mb-2">Top by Quantity</h3>
                                <ol class="space-y-1">
                                    <li
                                        v-for="p in topProducts.byQty"
                                        :key="p.id"
                                        class="flex justify-between"
                                    >
                                        <span>{{ p.name }}</span>
                                        <span class="font-medium">{{ p.qty }}</span>
                                    </li>
                                </ol>
                            </div>

                            <div>
                                <h3 class="font-semibold mb-2">Top by Revenue</h3>
                                <ol class="space-y-1">
                                    <li
                                        v-for="p in topProducts.byRevenue"
                                        :key="p.id"
                                        class="flex justify-between"
                                    >
                                        <span>{{ p.name }}</span>
                                        <span class="font-medium">{{ p.revenue }} €</span>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- Open sales -->
                    <TabPanel class="panel animate-fadeIn">
                        <ul class="space-y-3">
                            <li
                                v-for="sale in openSales"
                                :key="sale.id"
                                class="rounded-xl bg-yellow-50 ring-1 ring-yellow-200 px-4 py-3 shadow-sm"
                            >
                                🕒 Terminal {{ sale.terminal }} — open {{ sale.minutes }} min
                            </li>
                        </ul>
                    </TabPanel>

                    <!-- Cash -->
                    <TabPanel class="panel animate-fadeIn">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <Kpi title="Expected Cash" :value="cash.expected" suffix="€" />
                            <Kpi title="Cash Sales" :value="cash.salesCount" />
                            <Kpi title="Avg Ticket" :value="cash.avgTicket" suffix="€" />
                        </div>
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.panel {
    @apply rounded-2xl bg-white p-5 shadow-md ring-1 ring-gray-100;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.25s ease-out;
}
</style>
