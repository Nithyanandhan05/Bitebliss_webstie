<h1 class="page-title">Manage Promo Video</h1>

<div class="form-container">
    <h2>Upload New Video</h2>
    <p>Uploading a new video will replace the current one on the homepage.</p>
    <form action="pages/update_video.php" method="POST" enctype="multipart/form-data">
        <div class="input-group">
            <label for="video">Video File (MP4 format recommended)</label>
            <input type="file" name="video" accept="video/mp4" required>
        </div>
        <button type="submit">Update Video</button>
    </form>
</div>

<div class="table-container">
    <h2>Current Video</h2>
    <div class="video-preview-container">
        <video controls width="100%">
            <source src="../videos/brownie-ad.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
</div>