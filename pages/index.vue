<template>
  <div class="MainPage">
    <div class="Header mb-3">
      <page-header :icon="headerItem.icon">{{ headerItem.title }}</page-header>
      <div class="UpdatedAt">
        <span>{{ $t('最終更新') }}</span>
        <time :datetime="updatedAt">{{ Data.lastUpdate }}</time>
      </div>
      <div
        v-show="!['ja', 'ja-basic'].includes($i18n.locale)"
        class="Annotation"
      />
    </div>
    <static-info
      class="mb-4"
      url="https://www.city.yokohama.lg.jp/city-info/koho-kocho/koho/topics/covid-19/allNewsList.html"
      target="_blank"
      rel="noopener"
      :text="$t('横浜市発表の新型コロナウイルス感染症に関する最新情報はこちら')"
    />
    <card-row class="DataBlock">
      <!-- ワクチン接種状況 -->
      <vaccination-total-card />

      <!-- 陽性患者状況(7日移動平均) -->
      <seven-days-ave-card />

      <!-- 区別マップ表示 -->
      <map-card />

      <!-- 区別の検査陽性者 -->
      <ku-bar-card />

      <!-- 区別の検査陽性者 -->
      <ku-per100k-card />

      <!-- 区別の陽性者数積み上げ -->
      <ku-stack-card />

      <!-- 陽性患者状況(累計) -->
      <cumulative-total-card />

      <!-- 陽性患者状況(日ごと) -->
      <total-per-day-card />

      <!-- 年齢 -->
      <status-age-card />

      <!-- PCR検査数 -->
      <pcr-total-card />

      <!-- PCR検査数 -->
      <pcr-weerly-card />
    </card-row>
    <v-divider />
  </div>
</template>

<script lang="ts">
import Vue from 'vue'
import { MetaInfo } from 'vue-meta'
import Data from '@/data/data.json'

import PageHeader from '@/components/PageHeader.vue'
import StaticInfo from '@/components/StaticInfo.vue'

import CardRow from '@/components/cards/CardRow.vue'
import MapCard from '@/components/cards/MapCard.vue'
import KuBarCard from '@/components/cards/KuBarCard.vue'
import KuStackCard from '@/components/cards/KuStackCard.vue'
import PcrTotalCard from '@/components/cards/PcrTotalCard.vue'
import PcrWeerlyCard from '@/components/cards/PcrWeerlyCard.vue'
import StatusAgeCard from '@/components/cards/StatusAgeCard.vue'
import KuPer100kCard from '@/components/cards/KuPer100kCard.vue'
import TotalPerDayCard from '@/components/cards/TotalPerDayCard.vue'
import SevenDaysAveCard from '@/components/cards/SevenDaysAveCard.vue'
import CumulativeTotalCard from '@/components/cards/CumulativeTotalCard.vue'
import VaccinationTotalCard from '@/components/cards/VaccinationTotalCard.vue'
import { convertDatetimeToISO8601Format } from '@/utils/formatDate'

export default Vue.extend({
  components: {
    CardRow,
    MapCard,
    PageHeader,
    StaticInfo,
    KuBarCard,
    KuStackCard,
    PcrTotalCard,
    PcrWeerlyCard,
    KuPer100kCard,
    StatusAgeCard,
    TotalPerDayCard,
    SevenDaysAveCard,
    CumulativeTotalCard,
    VaccinationTotalCard
  },
  data() {
    const data = {
      Data,
      headerItem: {
        icon: 'mdi-chart-timeline-variant',
        title: this.$t('市内の最新感染動向')
      }
    }
    return data
  },
  computed: {
    updatedAt() {
      return convertDatetimeToISO8601Format(this.$data.Data.lastUpdate)
    }
  },
  head(): MetaInfo {
    return {
      title: this.$t('市内の最新感染動向') as string
    }
  }
})
</script>

<style lang="scss" scoped>
.MainPage {
  .Header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;

    @include lessThan($small) {
      flex-direction: column;
      align-items: baseline;
    }
  }

  .UpdatedAt {
    @include font-size(14);

    color: $gray-3;
    margin-bottom: 0.2rem;
  }

  .Annotation {
    @include font-size(12);

    color: $gray-3;
    @include largerThan($small) {
      margin: 0 0 0 auto;
    }
  }
  .DataBlock {
    margin: 20px -8px;

    .DataCard {
      @include largerThan($medium) {
        padding: 10px;
      }

      @include lessThan($small) {
        padding: 4px 8px;
      }
    }
  }
}
</style>
