<script setup lang="ts">
/**
 * User Notes Component (Vue)
 *
 * A client-side component for managing user notes.
 */

import { ref, onMounted, watch } from 'vue';
import type { Note } from '../lib/notes/api';
import {
  fetchNotes,
  createNote,
  updateNote,
  archiveNote,
  unarchiveNote,
  deleteNote,
} from '../lib/notes/api';

const notes = ref<Note[]>([]);
const loading = ref(true);
const showArchived = ref(false);
const editingNote = ref<Note | null>(null);
const newNote = ref({ title: '', body: '' });
const showNewNoteForm = ref(false);

const loadNotes = async () => {
  loading.value = true;
  const data = await fetchNotes(showArchived.value ? 'archived' : 'active');
  notes.value = data;
  loading.value = false;
};

const handleCreateNote = async () => {
  if (!newNote.value.title.trim() || !newNote.value.body.trim()) return;

  const created = await createNote(newNote.value.title, newNote.value.body);
  if (created) {
    notes.value = [created, ...notes.value];
    newNote.value = { title: '', body: '' };
    showNewNoteForm.value = false;
  }
};

const handleUpdateNote = async () => {
  if (!editingNote.value) return;

  const updated = await updateNote(editingNote.value.id, {
    title: editingNote.value.title,
    body: editingNote.value.body,
  });

  if (updated) {
    notes.value = notes.value.map((n) => (n.id === updated.id ? updated : n));
    editingNote.value = null;
  }
};

const handleArchive = async (id: number) => {
  const success = await archiveNote(id);
  if (success) {
    notes.value = notes.value.filter((n) => n.id !== id);
  }
};

const handleUnarchive = async (id: number) => {
  const success = await unarchiveNote(id);
  if (success) {
    notes.value = notes.value.filter((n) => n.id !== id);
  }
};

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to permanently delete this note?')) return;

  const success = await deleteNote(id);
  if (success) {
    notes.value = notes.value.filter((n) => n.id !== id);
  }
};

const startEdit = (note: Note) => {
  editingNote.value = { ...note };
};

const cancelEdit = () => {
  editingNote.value = null;
};

const cancelNewNote = () => {
  showNewNoteForm.value = false;
  newNote.value = { title: '', body: '' };
};

onMounted(() => {
  loadNotes();
});

watch(showArchived, () => {
  loadNotes();
});

</script>

<template>
  <div class="max-w-4xl mx-auto p-6">
    <div v-if="loading" class="text-center py-8">Loading notes...</div>

    <div v-else>
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">My Notes</h2>
        <div class="flex gap-3">
          <button
            @click="showArchived = !showArchived"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-medium"
          >
            {{ showArchived ? 'Show Active' : 'Show Archived' }}
          </button>
          <button
            v-if="!showArchived"
            @click="showNewNoteForm = true"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium"
          >
            + New Note
          </button>
        </div>
      </div>

      <!-- New Note Form -->
      <div
        v-if="showNewNoteForm"
        class="bg-white border-2 border-blue-500 rounded-lg p-6 mb-6 shadow-lg"
      >
        <form @submit.prevent="handleCreateNote">
          <input
            v-model="newNote.title"
            type="text"
            placeholder="Note title"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg mb-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          />
          <textarea
            v-model="newNote.body"
            placeholder="Note content (markdown supported)"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg mb-3 h-32 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          />
          <div class="flex gap-2">
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium"
            >
              Create Note
            </button>
            <button
              type="button"
              @click="cancelNewNote"
              class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- Notes List -->
      <div v-if="notes.length === 0" class="text-center py-12 text-gray-500">
        {{ showArchived ? 'No archived notes' : 'No notes yet. Create your first note!' }}
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="note in notes"
          :key="note.id"
          class="bg-white rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow"
        >
          <!-- Edit Mode -->
          <form
            v-if="editingNote?.id === note.id"
            @submit.prevent="handleUpdateNote"
            class="border-2 border-blue-500 rounded-lg p-6"
          >
            <input
              v-model="editingNote.title"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg mb-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required
            />
            <textarea
              v-model="editingNote.body"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg mb-3 h-32 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required
            />
            <div class="flex gap-2">
              <button
                type="submit"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium"
              >
                Save
              </button>
              <button
                type="button"
                @click="cancelEdit"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium"
              >
                Cancel
              </button>
            </div>
          </form>

          <!-- View Mode -->
          <div v-else>
            <div class="flex justify-between items-start mb-2">
              <h3 class="text-xl font-semibold text-gray-900">{{ note.title }}</h3>
              <span class="text-xs text-gray-500">
                {{ new Date(note.updated_at).toLocaleDateString() }}
              </span>
            </div>
            <p class="text-gray-700 whitespace-pre-wrap mb-4">{{ note.body }}</p>
            <div class="flex gap-2">
              <template v-if="!showArchived">
                <button
                  @click="startEdit(note)"
                  class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-sm font-medium"
                >
                  Edit
                </button>
                <button
                  @click="handleArchive(note.id)"
                  class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm font-medium"
                >
                  Archive
                </button>
              </template>
              <button
                v-else
                @click="handleUnarchive(note.id)"
                class="px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 rounded text-sm font-medium"
              >
                Unarchive
              </button>
              <button
                @click="handleDelete(note.id)"
                class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-sm font-medium"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
