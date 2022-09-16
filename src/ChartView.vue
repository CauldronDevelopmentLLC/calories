<script>
import util from './util.js'
import {Line} from 'vue-chartjs'
import {getRelativePosition} from 'chart.js/helpers'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  LinearScale,
  PointElement,
  CategoryScale
} from 'chart.js'


ChartJS.register(
  Title,
  Tooltip,
  Legend,
  LineElement,
  LinearScale,
  PointElement,
  CategoryScale
)

export default {
  name: 'ChartView',
  props: ['history'],
  components: {Line},


  computed: {
    vectors() {
      let sets = {
        weights:  this.history.weights  || [],
        goals:    this.history.goals    || [],
        calories: this.history.cal_hist || [],
        activity: this.history.act_hist || []
      }

      let accessor = {
        weights:  (e, last) => e.weight,
        goals:    (e, last) => e.goal   || last,
        calories: (e, last) => e.cals,
        activity: (e, last) => e.cals   || 0
      }

      // Get dates and prep data
      let start
      let finish
      let data  = {dates: []}
      let index = {}

      for (let name in sets) {
        let set = sets[name]

        if (set.length) {
          if (!start  || util.date_less(set[0].date, start))
            start  = set[0].date
          if (!finish || util.date_less(finish, set[set.length - 1].date))
            finish = set[set.length - 1].date
        }

        data[name]  = []
        index[name] = 0
      }

      if (!start || !finish) return data

      // Extract vectors
      let date = start
      let v = {}

      while (true) {
        for (let name in sets) {
          let e = sets[name][index[name]] || {}
          let match = e.date && util.date_eq(date, e.date)
          v[name] = accessor[name](match ? e : {}, v[name])
          data[name].push(v[name])
          if (match) index[name]++
        }

        data.dates.push(date)
        if (util.date_eq(date, finish)) break
        date = util.next_day(date)
      }

      data.changes  = []
      data.cal_diff = []
      data.cal_net  = []

      for (let i = 0; i < data.dates.length; i++) {
        let lastWeight = i ? data.weights[i - 1]  : 0
        let weight     = data.weights[i]
        let change     = (lastWeight ? weight - lastWeight : 0).toFixed(1)
        let cals       = data.calories[i]
        let goal       = data.goals[i]
        let cal_diff   = goal - cals
        let cal_net    = cals - data.activity[i]

        data.changes.push(change)
        data.cal_diff.push(cal_diff)
        data.cal_net.push(cal_net)
      }

      return data
    },


    chartData() {
      let weights  = this.vectors.weights
      let expAvgs  = util.to_fixed(util.exp_avg(weights), 1)

      let goals    = this.vectors.goals
      let calories = this.vectors.calories
      let activity = this.vectors.activity
      let cal_net  = util.exp_avg(this.vectors.cal_net, 0.8)

      return {
        labels: this.vectors.dates,
        datasets: [
          {
            label: 'Weight',
            data: weights,
            borderColor: '#00ff00',
            yAxisID: 'y'
          }, {
            label: 'Average',
            data: expAvgs,
            borderColor: '#00ffff',
            yAxisID: 'y'
          }, {
            label: 'Goals',
            data: goals,
            borderColor: '#666666',
            yAxisID: 'y1',
            hidden: false
          }, {
            label: 'Calories',
            data: calories,
            borderColor: '#ee8800',
            yAxisID: 'y1',
            hidden: true
          }, {
            label: 'Avg Net Cals',
            data: cal_net,
            borderColor: '#888800',
            yAxisID: 'y1'
          }, {
            label: 'Activity',
            data: activity,
            borderColor: '#88ee00',
            yAxisID: 'y1',
            hidden: true
          }
        ]
      }
    },

    chartOptions() {
      return {
        stacked: false,
        maintainAspectRatio: false,
        spanGaps: true,
        scales: {
          y:  {
            type: 'linear',
            display: true,
            position: 'left',
            title: {text: 'kgs', display: true}
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            title: {text: 'kcals', display: true},
            grid: {drawOnChartArea: false}
          },
        },
        onClick: this.click
      }
    }
  },


  methods: {
    click(e) {
      const p = getRelativePosition(e, e.chart)
      const elements = e.chart.getElementsAtEventForMode(e, 'point', {}, true)

      if (elements.length) {
        const i = e.chart.scales.x.getValueForPixel(p.x)
        const date = this.chartData.labels[i]
        const path = location.hash.substr(1).replace(/\?.*/, '')
        this.$router.push(path + '?date=' + date)
      }
    }
  }
}
</script>

<template lang="pug">
.chart-view
  .chart
    Line(:chartData="chartData", :chartOptions="chartOptions", height="400")

  .data(v-show="false")
    table
      tr
        th Date
        template(v-for="(list, name) in vectors")
          th(v-if="name != 'dates'") {{name}}

      tr(v-for="(date, index) in vectors.dates")
        td {{date}}

        template(v-for="(list, name) in vectors")
          td(v-if="name != 'dates'") {{list[index]}}
</template>

<style lang="stylus">
</style>
