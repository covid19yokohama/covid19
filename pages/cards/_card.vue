<template>
  <div>
    <confirmed-cases-details-card
      v-if="this.$route.params.card == 'ConfirmedCasesDetailsCard'"
    />

    <ku-bar-card v-else-if="this.$route.params.card == 'Cities'" />

    <map-card v-else-if="this.$route.params.card == 'MapCard'" />

    <ku-stack-card v-else-if="this.$route.params.card == 'agency'" />
  </div>
</template>

<script>
import Data from '@/data/data.json'
import agencyData from '@/data/ku-stack.json'
import KuBarCard from '@/components/cards/KuBarCard.vue'
import MapCard from '@/components/cards/MapCard.vue'
import KuStackCard from '@/components/cards/KuStackCard.vue'
import ConfirmedCasesDetailsCard from '@/components/cards/ConfirmedCasesDetailsCard.vue'

export default {
  components: {
    KuBarCard,
    MapCard,
    ConfirmedCasesDetailsCard,
    KuStackCard
  },
  data() {
    let title, updatedAt
    switch (this.$route.params.card) {
      case 'Cities':
        title = this.$t('区別 陽性者人数')
        updatedAt = Data.cities.date
        break
      case 'Map':
        title = this.$t('区別 陽性者人数マップ')
        updatedAt = Data.patients.date
        break
      case 'ConfirmedCasesDetailsCard':
        title = this.$t('検査陽性者の状況')
        updatedAt = Data.inspections_summary.date
        break
      //       case 'details-of-confirmed-cases':
      //         title = this.$t('検査陽性者の状況')
      //         updatedAt = Data.inspections_summary.date
      //         break
      //       case 'details-of-tested-cases':
      //         title = this.$t('検査実施状況')
      //         updatedAt = Data.inspection_status_summary.date
      //         break
      //       case 'number-of-confirmed-cases':
      //         title = this.$t('陽性患者数')
      //         updatedAt = Data.patients.date
      //         break
      //       case 'number-of-confirmed-cases-by-municipalities':
      //         title = this.$t('陽性患者数（区市町村別）')
      //         updatedAt = patientData.date
      //         break
      //       case 'attributes-of-confirmed-cases':
      //         title = this.$t('陽性患者の属性')
      //         updatedAt = Data.patients.date
      //         break
      //       case 'number-of-tested':
      //         title = this.$t('検査実施件数')
      //         updatedAt = Data.inspections_summary.date
      //         break
      //       case 'number-of-inspection-persons':
      //         title = this.$t('検査実施人数')
      //         updatedAt = Data.inspection_persons.date
      //         break
      //       case 'number-of-reports-to-covid19-telephone-advisory-center':
      //         title = this.$t('新型コロナコールセンター相談件数')
      //         updatedAt = Data.contacts.date
      //         break
      //       case 'number-of-reports-to-covid19-consultation-desk':
      //         title = this.$t('新型コロナ受診相談窓口相談件数')
      //         updatedAt = Data.querents.date
      //         break
      //       case 'predicted-number-of-toei-subway-passengers':
      //         title = this.$t('都営地下鉄の利用者数の推移')
      //         updatedAt = MetroData.date
      //         break
      case 'agency':
        title = this.$t('陽性患者数の推移')
        updatedAt = agencyData.date
        break
    }

    const data = {
      title,
      updatedAt
    }
    return data
  },
  head() {
    const url = 'https://covid19.yokohama'
    const timestamp = new Date().getTime()
    const ogpImage =
      this.$i18n.locale === 'ja'
        ? `${url}/ogp/${this.$route.params.card}.png?t=${timestamp}`
        : `${url}/ogp/${this.$i18n.locale}/${this.$route.params.card}.png?t=${timestamp}`
    const description = `${this.updatedAt} | ${this.$t(
      '当サイトは神奈川県横浜市の新型コロナウイルス感染症 (COVID-19) に関する最新情報を提供します。'
    )}`

    return {
      title: this.title,
      meta: [
        {
          hid: 'og:url',
          property: 'og:url',
          content: url + this.$route.path + '/'
        },
        {
          hid: 'og:title',
          property: 'og:title',
          content:
            this.title +
            ' | ' +
            this.$t('横浜市') +
            ' ' +
            this.$t('新型コロナウイルス感染症') +
            this.$t('対策サイト')
        },
        {
          hid: 'description',
          name: 'description',
          content: description
        },
        {
          hid: 'og:description',
          property: 'og:description',
          content: description
        },
        {
          hid: 'og:image',
          property: 'og:image',
          content: ogpImage
        },
        {
          hid: 'twitter:image',
          name: 'twitter:image',
          content: ogpImage
        }
      ]
    }
  }
}
</script>
