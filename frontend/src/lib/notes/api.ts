/**
 * User Notes API Client
 *
 * Provides functions to manage user notes.
 */
import { getCookie, ensureCsrfCookie } from '../auth';
const API_URL = import.meta.env.PUBLIC_API_URL || 'http://localhost:8000';

export interface Note {
  id: number;
  title: string;
  body: string;
  status: 'active' | 'archived';
  created_at: string;
  updated_at: string;
}

export interface NotesResponse {
  notes: Note[];
}

/**
 * Fetch all notes for the current user.
 */
export async function fetchNotes(status?: 'active' | 'archived'): Promise<Note[]> {
  try {
    const params = new URLSearchParams();
    if (status) params.set('status', status);

    const url = `${API_URL}/api/me/notes?${params}`;
    const response = await fetch(url, {
      credentials: 'include', // Include cookies for Sanctum auth
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch notes: ${response.statusText}`);
    }

    const data: NotesResponse = await response.json();
    return data.notes;
  } catch (error) {
    console.error('Error fetching notes:', error);
    return [];
  }
}

/**
 * Create a new note.
 */
export async function createNote(title: string, body: string): Promise<Note | null> {
  try {
    await ensureCsrfCookie()
    const response = await fetch(`${API_URL}/api/me/notes`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-xsrf-token': getCookie('XSRF-TOKEN'),
      },
      credentials: 'include',
      body: JSON.stringify({ title, body }),
    });

    if (!response.ok) {
      throw new Error(`Failed to create note: ${response.statusText}`);
    }

    return await response.json();
  } catch (error) {
    console.error('Error creating note:', error);
    return null;
  }
}

/**
 * Update an existing note.
 */
export async function updateNote(
  id: number,
  updates: { title?: string; body?: string }
): Promise<Note | null> {
  try {
    await ensureCsrfCookie()
    const response = await fetch(`${API_URL}/api/me/notes/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'x-xsrf-token': getCookie('XSRF-TOKEN'),
      },
      credentials: 'include',
      body: JSON.stringify(updates),
    });

    if (!response.ok) {
      throw new Error(`Failed to update note: ${response.statusText}`);
    }

    return await response.json();
  } catch (error) {
    console.error('Error updating note:', error);
    return null;
  }
}

/**
 * Archive a note.
 */
export async function archiveNote(id: number): Promise<boolean> {
  try {
    await ensureCsrfCookie()
    const response = await fetch(`${API_URL}/api/me/notes/${id}/archive`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'x-xsrf-token': getCookie('XSRF-TOKEN'),
      },
    });

    return response.ok;
  } catch (error) {
    console.error('Error archiving note:', error);
    return false;
  }
}

/**
 * Unarchive a note.
 */
export async function unarchiveNote(id: number): Promise<boolean> {
  try {
    await ensureCsrfCookie()
    const response = await fetch(`${API_URL}/api/me/notes/${id}/unarchive`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'x-xsrf-token': getCookie('XSRF-TOKEN'),
      },
    });

    return response.ok;
  } catch (error) {
    console.error('Error unarchiving note:', error);
    return false;
  }
}

/**
 * Delete a note permanently.
 */
export async function deleteNote(id: number): Promise<boolean> {
  try {
    await ensureCsrfCookie()
    const response = await fetch(`${API_URL}/api/me/notes/${id}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'x-xsrf-token': getCookie('XSRF-TOKEN'),
      },
    });

    return response.ok;
  } catch (error) {
    console.error('Error deleting note:', error);
    return false;
  }
}
