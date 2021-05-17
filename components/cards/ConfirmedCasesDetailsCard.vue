<template>
  <v-col cols="12" md="6" class="DataCard">
    <data-view
      :title="$t('検査陽性者の状況')"
      :source-title="$t('横浜市オープンデータポータル')"
      :source-url="
        $t('https://data.city.yokohama.lg.jp/dataset/kenko_corona-data')
      "
      :title-id="'details-of-confirmed-cases'"
      :date="updatedAt"
    >
      <confirmed-cases-details-table
        :aria-label="$t('検査陽性者の状況')"
        v-bind="confirmedCases"
      />
    </data-view>
  </v-col>
</template>

<script>
import dayjs from 'dayjs'
import Data from '@/data/data.json'
import formatConfirmedCases from '@/utils/formatConfirmedCases'
import DataView from '@/components/DataView.vue'
import ConfirmedCasesDetailsTable from '@/components/ConfirmedCasesDetailsTable.vue'

export default {
  components: {
    DataView,
    ConfirmedCasesDetailsTable
  },
  data() {
    // 検査陽性者の状況
    const confirmedCases = formatConfirmedCases(Data.main_summary)

    const updatedAt = dayjs(Data.main_summary.children[0].date).format(
      'YYYY/M/DD'
    )

    const data = {
      Data,
      confirmedCases,
      updatedAt
    }
    return data
  }
}
</script>
