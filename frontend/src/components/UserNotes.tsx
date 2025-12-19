/**
 * User Notes Component
 *
 * A client-side component for managing user notes.
 * Uses React for interactivity (requires Astro integration).
 */

import { useState, useEffect } from 'react';
import type { Note } from '../lib/notes/api';
import {
  fetchNotes,
  createNote,
  updateNote,
  archiveNote,
  unarchiveNote,
  deleteNote,
} from '../lib/notes/api';

export default function UserNotes() {
  const [notes, setNotes] = useState<Note[]>([]);
  const [loading, setLoading] = useState(true);
  const [showArchived, setShowArchived] = useState(false);
  const [editingNote, setEditingNote] = useState<Note | null>(null);
  const [newNote, setNewNote] = useState({ title: '', body: '' });
  const [showNewNoteForm, setShowNewNoteForm] = useState(false);

  useEffect(() => {
    loadNotes();
  }, [showArchived]);

  const loadNotes = async () => {
    setLoading(true);
    const data = await fetchNotes(showArchived ? 'archived' : 'active');
    setNotes(data);
    setLoading(false);
  };

  const handleCreateNote = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newNote.title.trim() || !newNote.body.trim()) return;

    const created = await createNote(newNote.title, newNote.body);
    if (created) {
      setNotes([created, ...notes]);
      setNewNote({ title: '', body: '' });
      setShowNewNoteForm(false);
    }
  };

  const handleUpdateNote = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingNote) return;

    const updated = await updateNote(editingNote.id, {
      title: editingNote.title,
      body: editingNote.body,
    });

    if (updated) {
      setNotes(notes.map((n) => (n.id === updated.id ? updated : n)));
      setEditingNote(null);
    }
  };

  const handleArchive = async (id: number) => {
    const success = await archiveNote(id);
    if (success) {
      setNotes(notes.filter((n) => n.id !== id));
    }
  };

  const handleUnarchive = async (id: number) => {
    const success = await unarchiveNote(id);
    if (success) {
      setNotes(notes.filter((n) => n.id !== id));
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to permanently delete this note?')) return;

    const success = await deleteNote(id);
    if (success) {
      setNotes(notes.filter((n) => n.id !== id));
    }
  };

  if (loading) {
    return <div className="text-center py-8">Loading notes...</div>;
  }

  return (
    <div className="max-w-4xl mx-auto p-6">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-2xl font-bold text-gray-900">My Notes</h2>
        <div className="flex gap-3">
          <button
            onClick={() => setShowArchived(!showArchived)}
            className="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-medium"
          >
            {showArchived ? 'Show Active' : 'Show Archived'}
          </button>
          {!showArchived && (
            <button
              onClick={() => setShowNewNoteForm(true)}
              className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium"
            >
              + New Note
            </button>
          )}
        </div>
      </div>

      {/* New Note Form */}
      {showNewNoteForm && (
        <div className="bg-white border-2 border-blue-500 rounded-lg p-6 mb-6 shadow-lg">
          <form onSubmit={handleCreateNote}>
            <input
              type="text"
              placeholder="Note title"
              value={newNote.title}
              onChange={(e) => setNewNote({ ...newNote, title: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg mb-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required
            />
            <textarea
              placeholder="Note content (markdown supported)"
              value={newNote.body}
              onChange={(e) => setNewNote({ ...newNote, body: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg mb-3 h-32 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required
            />
            <div className="flex gap-2">
              <button
                type="submit"
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium"
              >
                Create Note
              </button>
              <button
                type="button"
                onClick={() => {
                  setShowNewNoteForm(false);
                  setNewNote({ title: '', body: '' });
                }}
                className="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Notes List */}
      {notes.length === 0 ? (
        <div className="text-center py-12 text-gray-500">
          {showArchived ? 'No archived notes' : 'No notes yet. Create your first note!'}
        </div>
      ) : (
        <div className="space-y-4">
          {notes.map((note) =>
            editingNote?.id === note.id ? (
              <div key={note.id} className="bg-white border-2 border-blue-500 rounded-lg p-6 shadow-lg">
                <form onSubmit={handleUpdateNote}>
                  <input
                    type="text"
                    value={editingNote.title}
                    onChange={(e) => setEditingNote({ ...editingNote, title: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg mb-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                  />
                  <textarea
                    value={editingNote.body}
                    onChange={(e) => setEditingNote({ ...editingNote, body: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg mb-3 h-32 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                  />
                  <div className="flex gap-2">
                    <button
                      type="submit"
                      className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium"
                    >
                      Save
                    </button>
                    <button
                      type="button"
                      onClick={() => setEditingNote(null)}
                      className="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium"
                    >
                      Cancel
                    </button>
                  </div>
                </form>
              </div>
            ) : (
              <div key={note.id} className="bg-white rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
                <div className="flex justify-between items-start mb-2">
                  <h3 className="text-xl font-semibold text-gray-900">{note.title}</h3>
                  <span className="text-xs text-gray-500">
                    {new Date(note.updated_at).toLocaleDateString()}
                  </span>
                </div>
                <p className="text-gray-700 whitespace-pre-wrap mb-4">{note.body}</p>
                <div className="flex gap-2">
                  {!showArchived && (
                    <>
                      <button
                        onClick={() => setEditingNote(note)}
                        className="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-sm font-medium"
                      >
                        Edit
                      </button>
                      <button
                        onClick={() => handleArchive(note.id)}
                        className="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm font-medium"
                      >
                        Archive
                      </button>
                    </>
                  )}
                  {showArchived && (
                    <button
                      onClick={() => handleUnarchive(note.id)}
                      className="px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 rounded text-sm font-medium"
                    >
                      Unarchive
                    </button>
                  )}
                  <button
                    onClick={() => handleDelete(note.id)}
                    className="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-sm font-medium"
                  >
                    Delete
                  </button>
                </div>
              </div>
            )
          )}
        </div>
      )}
    </div>
  );
}
