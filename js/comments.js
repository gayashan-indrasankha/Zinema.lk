class CommentsSystem {
    constructor(options) {
        this.containerEl = options.container;
        this.movieId = options.movieId;
        this.apiEndpoint = options.apiEndpoint || '/api/comments.php';
        this.maxLength = options.maxLength || 500;
        this.currentUser = options.currentUser || {};
        
        this.init();
    }
    
    init() {
        this.render();
        this.loadComments();
        this.attachEventListeners();
    }
    
    render() {
        this.containerEl.innerHTML = `
            <div class="comments-section">
                <div class="comments-header">
                    <div class="comments-count">Comments</div>
                </div>
                
                <div class="comment-form">
                    <img src="${this.currentUser.avatar_url || 'assets/images/default-avatar.png'}" 
                         alt="" 
                         class="comment-avatar">
                    <div class="comment-input-wrapper">
                        <textarea class="comment-textarea" 
                                placeholder="Write a comment..."
                                maxlength="${this.maxLength}"></textarea>
                        <div class="comment-char-count">500</div>
                    </div>
                </div>
                
                <div class="comments-list"></div>
            </div>
        `;
        
        // Cache DOM elements
        this.commentsListEl = this.containerEl.querySelector('.comments-list');
        this.commentCountEl = this.containerEl.querySelector('.comments-count');
        this.mainTextarea = this.containerEl.querySelector('.comment-textarea');
    }
    
    async loadComments() {
        try {
            const response = await fetch(`${this.apiEndpoint}?movie_id=${this.movieId}`);
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            this.renderComments(data.comments);
            this.updateCommentCount(data.comments.length);
        } catch (error) {
            console.error('Failed to load comments:', error);
            this.showError('Failed to load comments. Please try again later.');
        }
    }
    
    renderComments(comments) {
        this.commentsListEl.innerHTML = comments
            .map(comment => this.renderComment(comment))
            .join('');
    }
    
    renderComment(comment, isReply = false) {
        const replies = comment.replies && comment.replies.length > 0
            ? `<div class="replies">
                ${comment.replies.map(reply => this.renderComment(reply, true)).join('')}
               </div>`
            : '';
            
        return `
            <div class="comment" data-comment-id="${comment.id}">
                <img src="${comment.avatar_url || 'assets/images/default-avatar.png'}" 
                     alt="" 
                     class="comment-avatar">
                     
                <div class="comment-content">
                    <div class="comment-header">
                        <a href="#" class="comment-author">${this.escapeHtml(comment.username)}</a>
                        <span class="comment-timestamp">${this.formatTimestamp(comment.created_at)}</span>
                    </div>
                    
                    <div class="comment-text">${this.escapeHtml(comment.content)}</div>
                    
                    <div class="comment-actions">
                        <a class="comment-action ${comment.user_liked ? 'liked' : ''}" data-action="like">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M8 14.25L6.862 13.222C3.205 9.916 1 7.952 1 5.5C1 3.536 2.536 2 4.5 2C5.898 2 7.281 2.781 8 4.069C8.719 2.781 10.102 2 11.5 2C13.464 2 15 3.536 15 5.5C15 7.952 12.795 9.916 9.138 13.222L8 14.25Z" 
                                      fill="currentColor"/>
                            </svg>
                            <span class="like-count">${comment.likes || 0}</span>
                        </a>
                        <a class="comment-action" data-action="reply">Reply</a>
                        ${this.currentUser.id === comment.user_id || this.currentUser.is_admin ? 
                          '<a class="comment-action" data-action="delete">Delete</a>' : 
                          ''}
                    </div>
                    
                    <div class="reply-form"></div>
                    ${replies}
                </div>
            </div>
        `;
    }
    
    attachEventListeners() {
        // Auto-expanding textarea
        this.containerEl.addEventListener('input', e => {
            if (e.target.classList.contains('comment-textarea')) {
                this.autoExpandTextarea(e.target);
                this.updateCharCount(e.target);
            }
        });
        
        // Form submission
        this.containerEl.addEventListener('keydown', e => {
            if (e.target.classList.contains('comment-textarea') && e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const textarea = e.target;
                const parentId = textarea.closest('.reply-form')?.dataset.parentId;
                this.submitComment(textarea, parentId);
            }
        });
        
        // Comment actions
        this.containerEl.addEventListener('click', e => {
            const action = e.target.closest('.comment-action');
            if (!action) return;
            
            const comment = action.closest('.comment');
            const commentId = comment.dataset.commentId;
            
            switch (action.dataset.action) {
                case 'like':
                    this.handleLike(commentId, action);
                    break;
                case 'reply':
                    this.showReplyForm(comment);
                    break;
                case 'delete':
                    this.handleDelete(commentId, comment);
                    break;
            }
        });
    }
    
    async submitComment(textarea, parentId = null) {
        const content = textarea.value.trim();
        if (!content) return;
        
        if (content.length > this.maxLength) {
            this.showError(`Comment cannot exceed ${this.maxLength} characters`);
            return;
        }
        
        const commentData = {
            movie_id: this.movieId,
            content: content,
            parent_id: parentId
        };
        
        try {
            textarea.disabled = true;
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(commentData)
            });
            
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (parentId) {
                // Add reply to existing thread
                const parentReplies = comment.querySelector('.replies') || 
                    this.createRepliesContainer(comment);
                parentReplies.insertAdjacentHTML('beforeend', this.renderComment(data.comment, true));
            } else {
                // Add new top-level comment
                this.commentsListEl.insertAdjacentHTML('afterbegin', this.renderComment(data.comment));
            }
            
            textarea.value = '';
            this.autoExpandTextarea(textarea);
            this.updateCommentCount();
        } catch (error) {
            console.error('Failed to submit comment:', error);
            this.showError('Failed to submit comment. Please try again.');
        } finally {
            textarea.disabled = false;
        }
    }
    
    async handleLike(commentId, likeButton) {
        try {
            const response = await fetch(`${this.apiEndpoint}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ comment_id: commentId })
            });
            
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            const countEl = likeButton.querySelector('.like-count');
            countEl.textContent = data.likes;
            likeButton.classList.toggle('liked', data.user_liked);
        } catch (error) {
            console.error('Failed to like comment:', error);
            this.showError('Failed to like comment. Please try again.');
        }
    }
    
    async handleDelete(commentId, commentEl) {
        if (!confirm('Are you sure you want to delete this comment?')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiEndpoint}?id=${commentId}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            commentEl.remove();
            this.updateCommentCount();
        } catch (error) {
            console.error('Failed to delete comment:', error);
            this.showError('Failed to delete comment. Please try again.');
        }
    }
    
    showReplyForm(commentEl) {
        const replyForm = commentEl.querySelector('.reply-form');
        
        if (replyForm.classList.contains('active')) {
            replyForm.classList.remove('active');
            return;
        }
        
        replyForm.innerHTML = `
            <img src="${this.currentUser.avatar_url || 'assets/images/default-avatar.png'}" 
                 alt="" 
                 class="comment-avatar">
            <div class="comment-input-wrapper">
                <textarea class="comment-textarea" 
                        placeholder="Write a reply..."
                        maxlength="${this.maxLength}"></textarea>
                <div class="comment-char-count">500</div>
            </div>
        `;
        
        replyForm.classList.add('active');
        replyForm.dataset.parentId = commentEl.dataset.commentId;
        
        const textarea = replyForm.querySelector('.comment-textarea');
        textarea.focus();
    }
    
    // Utility methods
    autoExpandTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }
    
    updateCharCount(textarea) {
        const charCount = textarea.closest('.comment-input-wrapper')
            .querySelector('.comment-char-count');
        const remaining = this.maxLength - textarea.value.length;
        charCount.textContent = remaining;
        charCount.style.color = remaining < 50 ? 'var(--warning-color, #f97316)' : '';
    }
    
    updateCommentCount() {
        const count = this.containerEl.querySelectorAll('.comment').length;
        this.commentCountEl.textContent = `Comments (${count})`;
    }
    
    formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = (now - date) / 1000; // seconds
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        if (diff < 2592000) return `${Math.floor(diff / 86400)}d ago`;
        
        return date.toLocaleDateString();
    }
    
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    createRepliesContainer(commentEl) {
        const replies = document.createElement('div');
        replies.className = 'replies';
        commentEl.querySelector('.comment-content').appendChild(replies);
        return replies;
    }
    
    showError(message) {
        // You can implement your own error display logic here
        alert(message);
    }
}

// Usage:
/*
const comments = new CommentsSystem({
    container: document.getElementById('comments-container'),
    movieId: 123,
    currentUser: {
        id: 1,
        username: 'John Doe',
        avatar_url: '/path/to/avatar.jpg',
        is_admin: false
    },
    maxLength: 500
});
*/