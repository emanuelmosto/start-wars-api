<template>
  <div class="app-root" :class="{ 'has-searched': hasSearched }">
    <header class="app-topbar">
      <div class="app-logo">SWStarter</div>
    </header>

    <div class="app-container">
      <main v-if="mode === 'search'" class="app-main">
        <section class="card search-card">
          <p class="search-title">What are you searching for?</p>

          <div class="search-type-toggle" aria-label="Search type">
            <label>
              <input
                type="radio"
                name="search-type"
                :checked="searchType === 'people'"
                @change="setSearchType('people')"
              />
              <span>People</span>
            </label>
            <label>
              <input
                type="radio"
                name="search-type"
                :checked="searchType === 'movies'"
                @change="setSearchType('movies')"
              />
              <span>Movies</span>
            </label>
          </div>

          <form class="search-form" @submit.prevent="onSearch">
            <div class="search-input-row">
              <input
                id="search-input"
                v-model="query"
                class="search-input"
                type="text"
                placeholder="e.g. Chewbacca, Yoda, Boba Fett"
                autocomplete="off"
              />
            </div>

            <button
              type="submit"
              class="search-button"
              :disabled="isSearchDisabled"
            >
              <span v-if="isSearching">Searching...</span>
              <span v-else>Search</span>
            </button>
          </form>

          <p v-if="error" class="error-message">{{ error }}</p>
        </section>

        <section class="card results-card">
          <header class="results-header">
            <h2 class="results-title">Results</h2>
            <div class="results-divider" />
          </header>

          <div v-if="isSearching && !results.length" class="results-empty">
            <p>Searching...</p>
          </div>

          <div
            v-else-if="!isSearching && !hasSearched"
            class="results-empty"
          >
            <p>There are zero matches.</p>
            <p class="results-helper">
              Use the form to search for People or Movies.
            </p>
          </div>

          <div
            v-else-if="!isSearching && hasSearched && !results.length"
            class="results-empty"
          >
            <p>There are zero matches.</p>
            <p class="results-helper">Try a different search term.</p>
          </div>

          <ul v-else class="results-list" aria-label="Search results">
            <li
              v-for="item in results"
              :key="item.id + '-' + item.type"
              class="result-item"
            >
              <div class="result-title">
                {{ item.label }}
              </div>
              <button
                type="button"
                class="result-button"
                @click="openDetails(item)"
              >
                See details
              </button>
            </li>
          </ul>
        </section>
      </main>

      <section
        v-else-if="mode === 'person' && selectedPerson"
        class="card details-card"
      >
        <header class="details-header">
          <h2 class="details-name">{{ selectedPerson.name }}</h2>
        </header>

        <div class="details-body details-body--two-column">
          <div class="details-column">
            <h3 class="details-section-title">Details</h3>
            <div class="details-section-divider" />

            <dl class="details-list">
              <div class="details-row">
                <dt>Birth Year:</dt>
                <dd>{{ selectedPerson.birth_year || 'Unknown' }}</dd>
              </div>
              <div class="details-row">
                <dt>Gender:</dt>
                <dd>{{ selectedPerson.gender || 'Unknown' }}</dd>
              </div>
              <div class="details-row">
                <dt>Eye Color:</dt>
                <dd>{{ selectedPerson.eye_color || 'Unknown' }}</dd>
              </div>
              <div class="details-row">
                <dt>Hair Color:</dt>
                <dd>{{ selectedPerson.hair_color || 'Unknown' }}</dd>
              </div>
              <div class="details-row">
                <dt>Height:</dt>
                <dd>{{ selectedPerson.height || 'Unknown' }}</dd>
              </div>
              <div class="details-row">
                <dt>Mass:</dt>
                <dd>{{ selectedPerson.mass || 'Unknown' }}</dd>
              </div>
            </dl>
          </div>

          <div class="details-column">
            <h3 class="details-section-title">Movies</h3>
            <div class="details-section-divider" />

            <ul class="details-links">
              <li
                v-for="movie in selectedPerson.movies"
                :key="movie.id"
              >
                {{ movie.title }}
              </li>
              <li v-if="!selectedPerson.movies.length" class="details-muted">
                No movies listed for this character.
              </li>
            </ul>
          </div>
        </div>

        <button
          type="button"
          class="back-primary-button"
          @click="backToSearch"
        >
          Back to search
        </button>
      </section>

      <section
        v-else-if="mode === 'movie' && selectedMovie"
        class="card details-card"
      >
        <header class="details-header">
          <h2 class="details-name">{{ selectedMovie.title }}</h2>
        </header>

        <div class="details-body details-body--two-column">
          <div class="details-column">
            <h3 class="details-section-title">Opening Crawl</h3>
            <div class="details-section-divider" />
            <p class="details-text">
              {{ selectedMovie.opening_crawl || 'No opening crawl available.' }}
            </p>
          </div>

          <div class="details-column">
            <h3 class="details-section-title">Characters</h3>
            <div class="details-section-divider" />

            <ul class="details-links">
              <li
                v-for="character in selectedMovie.characters"
                :key="character.id"
              >
                {{ character.name }}
              </li>
              <li v-if="!selectedMovie.characters.length" class="details-muted">
                No characters listed for this movie.
              </li>
            </ul>
          </div>
        </div>

        <button
          type="button"
          class="back-primary-button"
          @click="backToSearch"
        >
          Back to search
        </button>
      </section>
    </div>

    <section v-if="isLoadingDetails" class="loading-overlay" aria-live="polite">
      <div class="loading-spinner" />
      <span class="loading-text">Loading details...</span>
    </section>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'

const searchType = ref('people') // 'people' | 'movies'
const query = ref('')
const results = ref([])
const mode = ref('search') // 'search' | 'person' | 'movie'
const isSearching = ref(false)
const isLoadingDetails = ref(false)
const error = ref('')
const hasSearched = ref(false)

const selectedPerson = ref(null)
const selectedMovie = ref(null)

const api = axios.create({
  baseURL: '/api',
  timeout: 8000,
})

const isSearchDisabled = computed(
  () => isSearching.value || query.value.trim().length === 0,
)

function setSearchType(type) {
  if (type === searchType.value) {
    return
  }

  searchType.value = type
  results.value = []
  hasSearched.value = false
  error.value = ''
}

async function onSearch() {
  if (isSearchDisabled.value) {
    return
  }

  isSearching.value = true
  error.value = ''
  mode.value = 'search'
  selectedPerson.value = null
  selectedMovie.value = null

  try {
    const response = await api.get('/search', {
      params: {
        type: searchType.value,
        q: query.value.trim(),
      },
    })

    results.value = Array.isArray(response.data?.results)
      ? response.data.results
      : []
    hasSearched.value = true
  } catch (err) {
    console.error(err)
    error.value = 'Something went wrong while searching. Please try again.'
  } finally {
    isSearching.value = false
  }
}

function backToSearch() {
  mode.value = 'search'
  selectedPerson.value = null
  selectedMovie.value = null
}

async function openDetails(item) {
  if (!item || !item.id || !item.type) {
    return
  }

  if (item.type === 'person') {
    await loadPerson(item.id)
  } else {
    await loadMovie(item.id)
  }
}

async function loadPerson(id) {
  isLoadingDetails.value = true
  error.value = ''

  try {
    const { data } = await api.get(`/people/${id}`)
    selectedPerson.value = data
    mode.value = 'person'
  } catch (err) {
    console.error(err)
    error.value = 'Unable to load person details. Please try again.'
  } finally {
    isLoadingDetails.value = false
  }
}

async function loadMovie(id) {
  isLoadingDetails.value = true
  error.value = ''

  try {
    const { data } = await api.get(`/movies/${id}`)
    selectedMovie.value = data
    mode.value = 'movie'
  } catch (err) {
    console.error(err)
    error.value = 'Unable to load movie details. Please try again.'
  } finally {
    isLoadingDetails.value = false
  }
}
</script>

