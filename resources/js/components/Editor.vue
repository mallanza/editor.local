<template>
  <div>
    <h2>{{ title }}</h2>
    <div ref="editorContent"></div>
  </div>
</template>

<script setup>
import { onMounted, ref, onUnmounted } from 'vue'
import axios from 'axios'
import * as Y from 'yjs'
import { WebsocketProvider } from 'y-websocket'
import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Collaboration from '@tiptap/extension-collaboration'
import CollaborationCursor from '@tiptap/extension-collaboration-cursor'

const editorRef = ref(null)
const title = ref('Loading...')
const ydoc = new Y.Doc()
const docId = 1 // or use route param if dynamic

// WebSocket connection for Yjs
const provider = new WebsocketProvider('ws://localhost:1234', 'doc-1', ydoc)


let editor

onMounted(async () => {
  // Load document title from Laravel API
  const res = await axios.get(`/api/documents/${docId}`)
  title.value = res.data.title

  // Create and mount editor
  editor = new Editor({
    element: editorRef.value,
    extensions: [
      StarterKit,
      Collaboration.configure({ document: ydoc }),
      CollaborationCursor.configure({
        provider,
        user: {
          name: 'User ' + Math.floor(Math.random() * 1000),
          color: '#' + Math.floor(Math.random() * 16777215).toString(16),
        },
      }),
    ],
  })
})

onUnmounted(() => {
  editor?.destroy()
})
</script>
