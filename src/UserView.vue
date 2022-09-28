<script>
import util from './util.js'
import ChartView from './ChartView.vue'

export default {
  name: 'UserView',
  props: ['name', 'date'],
  components: {ChartView},


  data() {
    return {
      message: 'Loading...',
      user: {},

      new_cal_cals: '',
      new_cal_desc: '',
      new_act_cals: '',
      new_act_desc: '',
      new_weight: 0,
      new_goal: 0,
    }
  },


  watch: {
    date() {this.update()}
  },


  computed: {
    api_url() {return location.origin + '/api/user/' + this.name},


    age() {
      let dob  = new Date(this.user.dob)
      let diff = new Date(new Date(this.date) - dob.getTime())
      return diff.getUTCFullYear() - 1970
    },


    change() {
      return (this.user.weight - this.user.last_weight).toFixed(1)
    },


    bmr() {
      if (!this.user.sex) return 0

      const coef = {
        male:   [ 66, 2.2 * 6.23, 12.7 / 2.54, 6.8],
        female: [655, 2.2 * 4.35,  4.7 / 2.54, 4.7]
      }

      let c = coef[this.user.sex]

      return Math.round(
        c[0] + c[1] * this.user.weight + c[2] * this.user.height -
          c[3] * this.age)
    },


    smr() {return Math.round(this.bmr * 1.2)},
    cal_total() {return util.sum_key(this.user.calories, 'cals')},
    cal_remain() {return this.user.goal - this.cal_total},
    act_total() {return util.sum_key(this.user.activity, 'cals')},


    total_change() {
      let weights = this.user.weights
      if (!weights || !weights.length) return 0
      let c = weights[weights.length - 1].weight - weights[0].weight
      return c.toFixed(1)
    }
  },


  mounted() {this.update()},


  methods: {
    api_put(path, data) {
      fetch(this.api_url + path, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
      }).then(this.update)
    },


    api_delete(path, data) {
      let url = this.api_url + path + '?' + new URLSearchParams(data)
      fetch(url, {method: 'DELETE'}).then(this.update)
    },


    api_get(path, data) {
      let url = this.api_url + path + '?' + new URLSearchParams(data)
      return fetch(url).then(r => {
        if (r.status == 404) this.message = 'ERROR: User not found.'
        else if (r.status != 200) this.message = 'ERROR: ' + r.status
        else return r.json()
      })
    },


    update() {
      this.api_get('', {
        date: this.date,
        history: 90
      })
        .then(user => {
          if (!user) return

          this.message = ''
          user.calories = user.calories || []
          user.activity = user.activity || []
          user.weights  = user.weights  || []
          user.goals    = user.goals    || []

          this.user = user
          this.new_weight = user.weight.toFixed(1)
          this.new_goal = user.goal
          this.new_cal_cals = this.new_cal_desc = ''
          this.new_act_cals = this.new_act_desc = ''
        })
    },


    delete_cal_entry(id) {this.api_delete('/calories', {id})},


    add_cal() {
      this.api_put('/calories', {
        date: this.date,
        calories: this.new_cal_cals,
        description: this.new_cal_desc
      })
    },


    delete_act_entry(id) {this.api_delete('/activity', {id})},


    add_act() {
      this.api_put('/activity', {
        date: this.date,
        calories: this.new_act_cals,
        description: this.new_act_desc
      })
    },


    set_weight() {
      this.api_put('/weight', {date: this.date, weight: this.new_weight})
    },


    set_goal() {
      this.api_put('/goals', {date: this.date, goal: this.new_goal})
    },


    set_day(date = util.today()) {
      let path = '/user/' + this.name + (date ? '?date=' + date : '')
      if (path == this.$route.path && date == this.date) this.update()
      else this.$router.push(path)
    },


    add_day(x) {
      this.set_day(util.add_day(this.date, x))
    }
  }
}
</script>

<template lang="pug">
.user-view
  h2.message {{message}}

  .user-nav(v-if="user.sex")
    .name {{name}}
    button(@click="add_day(-7)"): .fa.fa-angle-double-left
    button(@click="add_day(-1)"): .fa.fa-angle-left
    .date {{new Date(date).toDateString()}}
    button(@click="add_day(1)"): .fa.fa-angle-right
    button(@click="add_day(7)"): .fa.fa-angle-double-right
    button(@click="set_day()") Today

  .profile
    table
      tr
        th Height
        td {{user.height}}cm
        th Age
        td {{age}}
        th Sex
        td {{user.sex}}
      tr
        th Weight
        td.weight
          form(@submit.prevent="set_weight")
            div
              input(v-model.number="new_weight", step="0.1", type="number")
              | kg
            button(type="sumit", title="Save weight."): .fa.fa-save
        th Change
        td(:class="{success: change < 0, error: 0 < change}") {{change}}kg
        th Total
        td(:class="{success: total_change < 0, error: 0 < total_change}")
          | {{total_change}}kg
      tr
        th Calorie Target
        td.target
          form(@submit.prevent="set_goal")
            div
              input(v-model.number="new_goal", type="number")
              | kcal
            button(type="submit", title="Save goal."): .fa.fa-save
        th BMR
        td {{bmr.toLocaleString()}}kcal
        th SMR
        td {{smr.toLocaleString()}}kcal

  .user(v-if="user.sex")
    .intake
      h3 Caloric Intake
      table
        tr
          th Calories
          th Description

        tr(v-for="e in user.calories")
          td.calories {{e.cals.toLocaleString()}}

          td.description
            div
              span {{e.item}}
              button(@click="delete_cal_entry(e.id)", title="Delete entry.")
                .fa.fa-trash

        tr
          td.calories
            form#cal-form(@submit.prevent="add_cal")
            input(v-model.number="new_cal_cals", type="number", name="calories",
              form="cal-form")

          td.description
            div
              input(v-model="new_cal_desc", name="cal_desc", form="cal-form")
              button(title="Add entry.", type="submit", form="cal-form")
                .fa.fa-plus

        tr
          td.calories {{cal_total.toLocaleString()}}
          th Total

        tr
          td.calories(:class="{error: cal_remain < 0}")
            | {{cal_remain.toLocaleString()}}
          th Remaining

    .activity
      h3 Activity
      table
        tr
          th Calories
          th Description

        tr(v-for="e in user.activity")
          td.calories {{e.cals.toLocaleString()}}

          td.description
            div
              span {{e.item}}
              button(@click="delete_act_entry(e.id)", title="Delete entry.")
                .fa.fa-trash

        tr
          td.calories
            form#act-form(@submit.prevent="add_act")
            input(v-model.number="new_act_cals", type="number", name="calories",
              form="act-form")

          td.description
            div
              input(v-model="new_act_desc", name="cal_desc", form="act-form")
              button(title="Add entry.", type="submit", form="act-form")
                .fa.fa-plus

        tr
          td.calories {{act_total.toLocaleString()}}
          th(colspan="2") Total

  ChartView(v-if="user.weights", :history="user")
</template>

<style lang="stylus">
.user-view
  text-align center

  .user-nav
    display flex
    flex-direction row
    gap 1em
    margin-bottom 1em
    justify-content center

    .name
      text-transform capitalize

    .name, .date
      font-weight bold
      font-size 120%

.user
  text-align left
  display grid
  grid-template-columns 1fr 1fr
  justify-content center
  gap 1em

  > *
    h3
      text-align center
      margin-bottom 0.25em

  table
    margin auto

    &.calories, &.activity
      width 100%

    td.calories
      text-align right

    td.calories input
      width 6em

    td.description
      width 100%

      > div
        display flex
        gap 0.25em

        > :nth-child(1)
          flex 1

    .target, .weight
      input
        width 4em

.profile table
  margin auto

  td
    text-align right

    > form
      display flex
      gap 0.25em
      text-align left

      input
        text-align right

      > :nth-child(1)
        flex 1

  input
    width 5em

.chart-view
  text-align left
  margin-top 2em

.error
  color red

.success
  color green
</style>
