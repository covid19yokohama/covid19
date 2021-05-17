<template>
  <v-col cols="12" md="6" class="DataCard">
    <agency-bar-chart
      :title="$t('PCR検査数(合計)')"
      :title-id="'agency'"
      :chart-id="'agency'"
      :chart-data="agencyData"
      :date="agencyData.date"
      :unit="$t('人')"
    />
  </v-col>
</template>

<script>
import agencyData from '@/data/pcr-total.json'
import AgencyBarChart from '@/components/PcrTotal.vue'

export default {
  components: {
    AgencyBarChart
  },
  data() {
    return {
      agencyData
    }
  }
}
</script>
