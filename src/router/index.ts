import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/Home.vue')
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue')
  },
  {
    path: '/regist',
    name: 'Register',
    component: () => import('@/views/Register.vue')
  },
  {
    path: '/friends',
    name: 'Friends',
    component: () => import('@/views/FriendsList.vue')
  },
  {
    path: '/chat/:id',
    name: 'Chat',
    component: () => import('@/views/Chat.vue')
  },
  {
    path: '/write/:id?',
    name: 'WriteArticle',
    component: () => import('@/views/WriteArticle.vue')
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/Profile.vue')
  },
  {
    path: '/user/:id',
    name: 'UserPage',
    component: () => import('@/views/UserPage.vue')
  }
]
const router = createRouter({
  history: createWebHistory(),
  routes
})
export default router