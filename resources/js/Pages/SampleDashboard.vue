<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Echo from 'laravel-echo'
const tabs = ['Dashboard', 'Stock alerts', 'Sales history', 'Products']

const kpis = ref({
    sales: '€1,240',
    transactions: 87,
    items: 312,
    cashCard: '40% / 60%',
})

const liveSales = ref([
    { id: 1, time: '14:02', product: 'Coca-Cola 0.5L', qty: 1, total: '€5.00' },
    { id: 2, time: '14:02', product: 'White Bread', qty: 2, total: '€6.00' },
    { id: 3, time: '14:03', product: 'Sale completed', qty: '', total: '€23.00' },
])

const stockAlerts = ref([
    { name: 'Milk 1L', message: '3 left', icon: '⚠', level: 'warning' },
    { name: 'Eggs L', message: 'Out of stock', icon: '⛔', level: 'critical' },
])

const topProducts = ref([
    { name: 'Bananas', value: '42 sold' },
    { name: 'Milk 1L', value: '38 sold' },
])

const cash = ref({
    expected: '€420',
    count: 34,
    avg: '€12.35',
})
const page = usePage()

// 🔥 initialize with backend data
const sales = ref(page.props.initialSales ?? [])
const stockEvents = ref(page.props.initialStockEvents ?? [])

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

    echo.private(`location.${locationId}`)
        .listen('.sale.completed', e => {
            sales.value.unshift({
                id: e.sale_id,
                total: e.total,
            })
        })
        .listen('.stock.updated', e => {
            stockEvents.value.unshift(e)
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
        <div class="w-full px-4 py-6">
            <TabGroup>
                <!-- Tabs -->
                <TabList class="flex space-x-1 rounded-xl bg-gray-900/10 p-1">
                    <Tab
                        v-for="tab in tabs"
                        :key="tab"
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
              'w-full rounded-lg py-2 text-sm font-medium',
              selected
                ? 'bg-white shadow text-gray-900'
                : 'text-gray-500 hover:bg-white/50',
            ]"
                        >
                            {{ tab }}
                        </button>
                    </Tab>
                </TabList>

                <!-- Panels -->
                <TabPanels class="mt-4">
                    <!-- DASHBOARD -->
                    <TabPanel class="space-y-6">
                        <!-- Today at a glance -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <Kpi label="Sales today" :value="kpis.sales" />
                            <Kpi label="Transactions" :value="kpis.transactions" />
                            <Kpi label="Items sold" :value="kpis.items" />
                            <Kpi label="Cash / Card" :value="kpis.cashCard" />
                        </div>

                        <!-- Live sales feed -->
                        <section>
                            <h2 class="font-semibold mb-2">Live sales</h2>
                            <ul class="divide-y rounded-lg border">
                                <li
                                    v-for="sale in liveSales"
                                    :key="sale.id"
                                    class="flex justify-between px-3 py-2 text-sm"
                                >
                                    <span class="text-gray-500">{{ sale.time }}</span>
                                    <span>{{ sale.product }} ×{{ sale.qty }}</span>
                                    <span class="font-medium">{{ sale.total }}</span>
                                </li>
                            </ul>
                        </section>

                        <!-- Top products -->
                        <section>
                            <h2 class="font-semibold mb-2">Top products today 🔥</h2>
                            <ul class="text-sm list-disc ml-4">
                                <li v-for="p in topProducts" :key="p.name">
                                    {{ p.name }} — {{ p.value }}
                                </li>
                            </ul>
                        </section>

                        <!-- Cash control -->
                        <section>
                            <h2 class="font-semibold mb-2">Cash control 💵</h2>
                            <div class="text-sm space-y-1">
                                <div>Expected cash: {{ cash.expected }}</div>
                                <div>Cash sales: {{ cash.count }}</div>
                                <div>Average ticket: {{ cash.avg }}</div>
                            </div>
                        </section>
                    </TabPanel>

                    <!-- STOCK ALERTS -->
                    <TabPanel>
                        <h2 class="font-semibold mb-2">Stock alerts 🚨</h2>
                        <ul class="space-y-2">
                            <li
                                v-for="item in stockAlerts"
                                :key="item.name"
                                class="rounded border p-3"
                                :class="item.level === 'critical' ? 'border-red-500' : 'border-yellow-400'"
                            >
                                {{ item.icon }} {{ item.name }} — {{ item.message }}
                            </li>
                        </ul>
                    </TabPanel>

                    <!-- SALES HISTORY -->
                    <TabPanel>
                        <h2 class="font-semibold mb-2">Sales history</h2>
                        <p class="text-sm text-gray-500">
                            Read-only list of completed sales.
                        </p>
                    </TabPanel>

                    <!-- PRODUCTS -->
                    <TabPanel>
                        <h2 class="font-semibold mb-2">Products</h2>
                        <p class="text-sm text-gray-500">
                            Read-only product catalog.
                        </p>
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>



    </AuthenticatedLayout>
</template>

<style scoped>

</style>
