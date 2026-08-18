import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import {
    completeFolderTask as completeFolderTaskRequest,
    createFolderTask as createFolderTaskRequest,
    deleteFolderTask as deleteFolderTaskRequest,
    listFolderTasks as listFolderTasksRequest,
} from '@/api/folder-tasks.js'

export const useFolderTasksStore = defineStore('folder-tasks', () => {
    const tasks = ref([])

    const count = computed(() => tasks.value.length)

    function sortTasks() {
        tasks.value = [...tasks.value].sort((left, right) => {
            if (left.status !== right.status) {
                if (left.status === 'pending') {
                    return -1
                }

                if (right.status === 'pending') {
                    return 1
                }
            }

            if (left.due_at && !right.due_at) {
                return -1
            }

            if (!left.due_at && right.due_at) {
                return 1
            }

            if (left.due_at && right.due_at) {
                return new Date(left.due_at).getTime() - new Date(right.due_at).getTime()
            }

            return Number(left.id) - Number(right.id)
        })
    }

    async function fetchTasks(folderId) {
        const response = await listFolderTasksRequest(folderId)

        tasks.value = Array.isArray(response.data) ? response.data : []

        return tasks.value
    }

    async function createTask(folderId, payload) {
        const response = await createFolderTaskRequest(folderId, payload)

        const created = response.data

        tasks.value.push(created)

        sortTasks()

        return created
    }

    async function completeTask(folderId, taskId) {
        const response = await completeFolderTaskRequest(folderId, taskId)

        const completed = response.data

        const index = tasks.value.findIndex((task) => Number(task.id) === Number(taskId))

        if (index !== -1) {
            tasks.value[index] = completed
        }

        sortTasks()

        return completed
    }

    async function removeTask(folderId, taskId) {
        await deleteFolderTaskRequest(folderId, taskId)

        tasks.value = tasks.value.filter((task) => Number(task.id) !== Number(taskId))
    }

    function clear() {
        tasks.value = []
    }

    return {
        tasks,
        count,

        fetchTasks,
        createTask,
        completeTask,
        removeTask,

        clear,
    }
})
