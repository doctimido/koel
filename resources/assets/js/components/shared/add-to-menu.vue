<template>
    <div class="add-to-playlist" v-show="showing" v-on-clickaway="hideMenu">
        <p>Add {{ songs.length }} {{ songs.length | pluralize 'song' }} to</p>

        <ul>
            <li v-if="mergedSettings.canQueue" @click="queueSongsToBottom">Bottom of Queue</li>
            <li v-if="mergedSettings.canQueue" @click="queueSongsToTop">Top of Queue</li>
            <li v-if="mergedSettings.canLike" @click="addSongsToFavorite">Favorites</li>
            <li v-for="playlist in playlistState.playlists" @click="addSongsToExistingPlaylist(playlist)">{{ playlist.name }}</li>
        </ul>

        <p>or create a new playlist</p>

        <form class="form-save form-simple" @submit.prevent="createNewPlaylistFromSongs">
            <input type="text"
                @keyup.esc.prevent="hideMenu"
                v-model="newPlaylistName"
                placeholder="Playlist name"
                required>
            <button type="submit">
                <i class="fa fa-save"></i>
            </button>
        </form>
    </div>
</template>

<script>
    import _ from 'lodash';
    import VueClickaway from 'vue-clickaway';

    import playlistStore from '../../stores/playlist';
    import favoriteStore from '../../stores/favorite';
    import queueStore from '../../stores/queue';

    export default {
        props: ['songs', 'showing', 'settings'],
        mixins: [ VueClickaway.mixin ],

        data() {
            return {
                newPlaylistName: '',
                playlistState: playlistStore.state,
                mergedSettings: _.assign({
                    canQueue: true,
                    canLike: true,
                }, this.settings),
            };
        },

        watch: {
            songs() {
                if (!this.songs.length) {
                    this.hideMenu();
                }
            },
        },

        methods: {
            /**
             * Add the selected songs into Favorite.
             */
            addSongsToFavorite() {
                favoriteStore.like(this.songs, () => {
                    // Nothing much now.
                });

                this.hideMenu();
                this.clearSelection();
            },

            /**
             * Queue selected songs to bottom of queue.
             */
            queueSongsToBottom() {
                queueStore.queue(this.songs);

                this.hideMenu();
                this.clearSelection();
            },

            /**
             * Queue selected songs to top of queue.
             */
            queueSongsToTop() {
                queueStore.queue(this.songs, false, true);

                this.hideMenu();
                this.clearSelection();
            },

            /**
             * Add the selected songs into the chosen playlist.
             *
             * @param {Object} playlist The playlist.
             */
            addSongsToExistingPlaylist(playlist) {
                playlistStore.addSongs(playlist, this.songs, () => {
                    // Nothing much now.
                });

                this.hideMenu();
                this.clearSelection();
            },

            /**
             * Save the selected songs as a playlist.
             * As of current we don't have selective save.
             */
            createNewPlaylistFromSongs() {
                this.newPlaylistName = this.newPlaylistName.trim();

                if (!this.newPlaylistName) {
                    return;
                }

                playlistStore.store(this.newPlaylistName, this.songs, () => {
                    this.newPlaylistName = '';

                    // Activate the new playlist right away
                    this.$root.loadPlaylist(_.last(this.playlistState.playlists));
                });

                this.hideMenu();
                this.clearSelection();
            },

            hideMenu() {
                this.$dispatch('add-to-menu:close');
            },

            clearSelection() {
                this.$parent.$broadcast('song:selection-clear');
            },
        },
    };
</script>

<style lang="sass" scoped>
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    .add-to-playlist {
        @include context-menu();

        position: absolute;
        top: 36px;
        left: 0;
        width: 100%;

        p {
            margin: 4px 0;
            font-size: 90%;

            &::first-of-type {
                margin-top: 0;
            }
        }

        $itemHeight: 28px;
        $itemMargin: 2px;

        ul {
            max-height: 5 * ($itemHeight + $itemMargin);
            overflow-y: scroll;
            -webkit-overflow-scrolling: touch;
        }

        li {
            cursor: pointer;
            background: rgba(255, 255, 255, .2);
            height: $itemHeight;
            line-height: $itemHeight;
            padding: 0 8px;
            margin: $itemMargin 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-radius: 3px;
            background: #fff;

            &:hover {
                background: $colorHighlight;
                color: #fff;
            }
        }

        &::before {
            display: block;
            content: " ";
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 10px solid rgb(232, 232, 232);
            position: absolute;
            top: -7px;
            left: calc(50% - 10px);
        }

        form {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;

            input[type="text"] {
                width: 100%;
                border-radius: 5px 0 0 5px;
                height: 28px;
            }

            button[type="submit"] {
                margin-top: 0;
                border-radius: 0 5px 5px 0 !important;
                height: 28px;
                margin-left: -2px !important;
            }
        }
    }
</style>
