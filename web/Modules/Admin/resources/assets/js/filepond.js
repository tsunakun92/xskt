import * as FilePond from 'filepond';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';

FilePond.registerPlugin(FilePondPluginFileValidateSize);
FilePond.registerPlugin(FilePondPluginImagePreview);

window.FilePond = FilePond;
window.FilePondPluginFileValidateSize = FilePondPluginFileValidateSize;
window.FilePondPluginImagePreview = FilePondPluginImagePreview;

export default FilePond;
