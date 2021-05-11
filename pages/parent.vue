<template>
  <div class="Parent">
    <page-header class="mb-3">
      {{ $t('臨時休校中の新型コロナウイルス感染症対応についてのお願い') }}
    </page-header>
    <static-card>
      <h3>{{ $t('感染予防・健康管理') }}</h3>
      <ul>
        <li>
          {{
            $t(
              '不特定多数の人の集まる場所等への外出を避け、基本的に自宅で過ごしてください。'
            )
          }}
        </li>
        <li>
          {{ $t('手洗い、咳エチケット等により、感染予防に努めてください。')
          }}<br />
          <external-link
            url="https://tokyodouga.jp/lViN9C_BS-0.html"
            :icon-size="16"
            >{{ $t('【参考】感染症予防のための正しい手洗い方法（動画）') }}
          </external-link>
        </li>
        <li>
          {{
            $t(
              '規則正しい生活を心がけ、日常の健康管理に十分気を付けてください。'
            )
          }}
        </li>
      </ul>
    </static-card>
    <static-card>
      <h3>{{ $t('感染症を疑う場合の対応') }}</h3>
      <ul>
        <li>
          {{
            $t(
              '「新型コロナウイルス感染症帰国者・接触者相談センターにご相談いただく目安」に該当する方は、「新型コロナウイルス感染症帰国者・接触者相談センター」にご相談ください。'
            )
          }}
        </li>
        <external-link
          url="https://www.city.yokohama.lg.jp/kurashi/kenko-iryo/yobosesshu/kansensho/ncov-soudan.html#0BE33"
          :icon-size="16"
          >{{
            $t(
              '「新型コロナウイルス感染症帰国者・接触者相談センターにご相談いただく目安」'
            )
          }}
        </external-link>
        <br />
        <external-link
          url="https://www.city.yokohama.lg.jp/kurashi/kenko-iryo/yobosesshu/kansensho/ncov-soudan.html#126A3"
          :icon-size="16"
          >{{ $t('「新型コロナウイルス感染症帰国者・接触者相談センター」') }}
        </external-link>
      </ul>
    </static-card>
    <static-card>
      <h3>{{ $t('その他.parent') }}</h3>
      <p>{{ $t('詳細は、各学校からのお知らせ等をご確認ください。') }}</p>
    </static-card>
  </div>
</template>

<script lang="ts">
import Vue from 'vue'
import { MetaInfo } from 'vue-meta'
import StaticCard from '@/components/StaticCard.vue'
import PageHeader from '@/components/PageHeader.vue'
import ExternalLink from '@/components/ExternalLink.vue'

export default Vue.extend({
  components: {
    PageHeader,
    StaticCard,
    ExternalLink
  },
  head(): MetaInfo {
    return {
      title: this.$t('お子様をお持ちの皆様へ') as string
    }
  }
})
</script>
