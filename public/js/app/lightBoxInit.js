function showImage(filePath) {
    let lightbox = new FsLightbox();
    lightbox.props.sources = [filePath];
    lightbox.open();
}

