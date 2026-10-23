<?php
class TrailerManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetch all trailers with movie details and user interactions
     * @param int|null $userId Current user ID for personalized data
     * @param int $limit Number of trailers to fetch
     * @param int $offset Offset for pagination
     * @return array Trailers with movie details and user interactions
     */
    public function getAllTrailers($userId = null, $limit = 20, $offset = 0) {
        $sql = "
            SELECT 
                t.*,
                m.title AS movie_title,
                m.poster_url,
                m.release_date,
                m.genre,
                m.duration,
                m.rating,
                COUNT(DISTINCT tv.id) as view_count,
                COUNT(DISTINCT tl.id) as like_count,
                COUNT(DISTINCT tf.id) as favorite_count,
                COUNT(DISTINCT ts.id) as share_count,
                CASE WHEN utl.id IS NOT NULL THEN 1 ELSE 0 END as user_liked,
                CASE WHEN utf.id IS NOT NULL THEN 1 ELSE 0 END as user_favorited
            FROM trailers t
            INNER JOIN movies m ON t.movie_id = m.id
            LEFT JOIN trailer_views tv ON t.id = tv.trailer_id
            LEFT JOIN trailer_likes tl ON t.id = tl.trailer_id
            LEFT JOIN trailer_favorites tf ON t.id = tf.trailer_id
            LEFT JOIN trailer_shares ts ON t.id = ts.trailer_id
            LEFT JOIN trailer_likes utl ON t.id = utl.trailer_id AND utl.user_id = :user_id
            LEFT JOIN trailer_favorites utf ON t.id = utf.trailer_id AND utf.user_id = :user_id
            GROUP BY t.id
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single trailer with all related data
     * @param int $trailerId Trailer ID
     * @param int|null $userId Current user ID
     * @return array|false Trailer data or false if not found
     */
    public function getTrailer($trailerId, $userId = null) {
        $sql = "
            SELECT 
                t.*,
                m.title AS movie_title,
                m.poster_url,
                m.release_date,
                m.genre,
                m.duration,
                m.rating,
                m.description,
                COUNT(DISTINCT tv.id) as view_count,
                COUNT(DISTINCT tl.id) as like_count,
                COUNT(DISTINCT tf.id) as favorite_count,
                COUNT(DISTINCT ts.id) as share_count,
                CASE WHEN utl.id IS NOT NULL THEN 1 ELSE 0 END as user_liked,
                CASE WHEN utf.id IS NOT NULL THEN 1 ELSE 0 END as user_favorited
            FROM trailers t
            INNER JOIN movies m ON t.movie_id = m.id
            LEFT JOIN trailer_views tv ON t.id = tv.trailer_id
            LEFT JOIN trailer_likes tl ON t.id = tl.trailer_id
            LEFT JOIN trailer_favorites tf ON t.id = tf.trailer_id
            LEFT JOIN trailer_shares ts ON t.id = ts.trailer_id
            LEFT JOIN trailer_likes utl ON t.id = utl.trailer_id AND utl.user_id = :user_id
            LEFT JOIN trailer_favorites utf ON t.id = utf.trailer_id AND utf.user_id = :user_id
            WHERE t.id = :trailer_id
            GROUP BY t.id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':trailer_id', $trailerId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Increment view count for a trailer
     * @param int $trailerId Trailer ID
     * @param int $userId User ID
     * @param string $ipAddress Viewer's IP address
     * @return bool Success status
     */
    public function incrementViewCount($trailerId, $userId, $ipAddress) {
        // Check if this view was already counted in the last hour
        $sql = "
            SELECT id FROM trailer_views 
            WHERE trailer_id = :trailer_id 
            AND (user_id = :user_id OR ip_address = :ip_address)
            AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':trailer_id', $trailerId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':ip_address', $ipAddress, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->fetch()) {
            return false; // View already counted
        }

        // Record new view
        $sql = "
            INSERT INTO trailer_views (trailer_id, user_id, ip_address, viewed_at)
            VALUES (:trailer_id, :user_id, :ip_address, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':trailer_id', $trailerId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':ip_address', $ipAddress, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Toggle like status for a trailer
     * @param int $trailerId Trailer ID
     * @param int $userId User ID
     * @return array Updated like count and status
     */
    public function toggleLike($trailerId, $userId) {
        // Check if already liked
        $sql = "SELECT id FROM trailer_likes WHERE trailer_id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$trailerId, $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Unlike
            $sql = "DELETE FROM trailer_likes WHERE trailer_id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$trailerId, $userId]);
            $liked = false;
        } else {
            // Like
            $sql = "INSERT INTO trailer_likes (trailer_id, user_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$trailerId, $userId]);
            $liked = true;
        }

        // Get updated like count
        $sql = "SELECT COUNT(*) as count FROM trailer_likes WHERE trailer_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$trailerId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'liked' => $liked,
            'count' => $count
        ];
    }

    /**
     * Toggle favorite status for a trailer
     * @param int $trailerId Trailer ID
     * @param int $userId User ID
     * @return array Updated favorite count and status
     */
    public function toggleFavorite($trailerId, $userId) {
        // Check if already favorited
        $sql = "SELECT id FROM trailer_favorites WHERE trailer_id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$trailerId, $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Unfavorite
            $sql = "DELETE FROM trailer_favorites WHERE trailer_id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$trailerId, $userId]);
            $favorited = false;
        } else {
            // Favorite
            $sql = "INSERT INTO trailer_favorites (trailer_id, user_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$trailerId, $userId]);
            $favorited = true;
        }

        // Get updated favorite count
        $sql = "SELECT COUNT(*) as count FROM trailer_favorites WHERE trailer_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$trailerId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'favorited' => $favorited,
            'count' => $count
        ];
    }

    /**
     * Record a share event for a trailer
     * @param int $trailerId Trailer ID
     * @param int $userId User ID
     * @param string $platform Platform where trailer was shared
     * @return bool Success status
     */
    public function recordShare($trailerId, $userId, $platform) {
        $sql = "
            INSERT INTO trailer_shares (trailer_id, user_id, platform, shared_at)
            VALUES (:trailer_id, :user_id, :platform, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':trailer_id', $trailerId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':platform', $platform, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Get related trailers based on movie genre and release date
     * @param int $trailerId Current trailer ID
     * @param int $limit Number of related trailers to fetch
     * @return array Related trailers
     */
    public function getRelatedTrailers($trailerId, $limit = 6) {
        $sql = "
            SELECT 
                t2.*,
                m2.title AS movie_title,
                m2.poster_url,
                m2.release_date,
                m2.genre,
                COUNT(DISTINCT tv.id) as view_count
            FROM trailers t1
            INNER JOIN movies m1 ON t1.movie_id = m1.id
            INNER JOIN trailers t2 ON t2.id != t1.id
            INNER JOIN movies m2 ON t2.movie_id = m2.id
            LEFT JOIN trailer_views tv ON t2.id = tv.trailer_id
            WHERE t1.id = :trailer_id
            AND (
                m2.genre = m1.genre
                OR YEAR(m2.release_date) = YEAR(m1.release_date)
            )
            GROUP BY t2.id
            ORDER BY 
                CASE WHEN m2.genre = m1.genre THEN 1 ELSE 2 END,
                ABS(DATEDIFF(m2.release_date, m1.release_date)),
                view_count DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':trailer_id', $trailerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get trending trailers based on recent views, likes, and shares
     * @param int $limit Number of trailers to fetch
     * @param int $days Number of days to consider for trending calculation
     * @return array Trending trailers
     */
    public function getTrendingTrailers($limit = 10, $days = 7) {
        $sql = "
            SELECT 
                t.*,
                m.title AS movie_title,
                m.poster_url,
                m.release_date,
                m.genre,
                COUNT(DISTINCT tv.id) as view_count,
                COUNT(DISTINCT tl.id) as like_count,
                COUNT(DISTINCT ts.id) as share_count,
                (
                    COUNT(DISTINCT CASE WHEN tv.viewed_at > DATE_SUB(NOW(), INTERVAL ? DAY) THEN tv.id END) * 1 +
                    COUNT(DISTINCT CASE WHEN tl.created_at > DATE_SUB(NOW(), INTERVAL ? DAY) THEN tl.id END) * 2 +
                    COUNT(DISTINCT CASE WHEN ts.shared_at > DATE_SUB(NOW(), INTERVAL ? DAY) THEN ts.id END) * 3
                ) as trending_score
            FROM trailers t
            INNER JOIN movies m ON t.movie_id = m.id
            LEFT JOIN trailer_views tv ON t.id = tv.trailer_id
            LEFT JOIN trailer_likes tl ON t.id = tl.trailer_id
            LEFT JOIN trailer_shares ts ON t.id = ts.trailer_id
            GROUP BY t.id
            HAVING trending_score > 0
            ORDER BY trending_score DESC
            LIMIT ?
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$days, $days, $days, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}