<script>
export default {
  name: 'UsersView',

  data() {
    return {
      name: '',
      height: 0,
      dob: '',
      sex: 'male',
      users: []
    }
  },


  mounted() {this.update()},


  methods: {
    update() {
      fetch(location.origin + '/api/user')
        .then(r => r.json())
        .then(users => this.users = users)
    },


    'delete'() {
      alert('NYI')
    },


    add() {
      alert('NYI')
    }
  }
}
</script>

<template lang="pug">
table.users
  tr
    th Name
    th Height
    th Age
    th Sex
    th Action

  tr(v-for="user in users")
    td: router-link(:to="'/user/' + user.name") {{user.name}}
    td {{user.height}}cm
    td {{user.age}}
    td {{user.sex}}
    td: button(@click="delete(user.name)") Delete

  tr
    td: input(v-model="name")
    td: input(v-model.number="height")
    td: input(v-model="dob")
    td
      select(v-model="sex")
        option(value="male") Male
        option(value="female") Female
    td: button(@click="add()") Add
</template>

<style lang="stylus">
</style>
