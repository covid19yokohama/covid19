<template>
  <v-col cols="12" md="6" class="DataCard">
    <agency-bar-chart
      :title="$t('陽性患者の発生状況(日ごと)')"
      :title-id="'patients-status'"
      :chart-id="'patients-status'"
      :chart-data="agencyData"
      :date="agencyData.date"
      :unit="$t('人')"
    />
  </v-col>
</template>

<script>
import agencyData from '@/data/total-per-day.json'
import AgencyBarChart from '@/components/TotalPerDay.vue'

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
