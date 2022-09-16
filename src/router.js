import {createRouter, createWebHashHistory} from 'vue-router'
import UsersView from './UsersView.vue'
import UserView from './UserView.vue'
import util from './util.js'


export default createRouter({
  history: createWebHashHistory(),
  routes: [
    {path: '/', component: UsersView},
    {
      path: '/user/:name',
      component: UserView,
      props: route => ({
        name: route.params.name,
        date: route.query.date || util.today()
      })
    },
  ]
})
