<template>
  <v-col cols="12" md="6" class="DataCard">
    <cities-bar-chart
      :title="$t('区別 10万人あたりの陽性患者 発生数')"
      :title-id="'cities'"
      :chart-id="'cities'"
      :chart-data="citiesData"
      :date="citiesData.date"
      :unit="$t('人')"
    />
  </v-col>
</template>

<script>
import Data from '@/data/ku-per-100k.json'
import CitiesBarChart from '@/components/KuPer100k.vue'

export default {
  components: {
    CitiesBarChart
  },
  data() {
    const cities = [this.$t('陽性患者数')]
    const citiesData = Data.data
    return {
      citiesData,
      cities
    }
  }
}
</script>
