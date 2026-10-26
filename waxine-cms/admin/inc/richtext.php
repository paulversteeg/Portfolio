<?php /* Rich-text editor (TinyMCE, self-hosted GPL build from cdnjs). Included by the post & page editors. */ ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
  selector: 'textarea[name="body_html"]',
  license_key: 'gpl',            // self-hosted GPL usage, no cloud account
  height: 520,
  menubar: false,
  branding: false,
  plugins: 'lists link image table code blockquote autolink',
  toolbar: 'undo redo | blocks | bold italic | bullist numlist | link image blockquote table | removeformat | code',
  block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3',
  convert_urls: false,           // keep absolute/relative URLs as typed
  // Preserve the site's existing custom markup on round-trip (figures,
  // addresses, dropcap spans, the resume/talks/publication classes, etc.)
  valid_elements: '*[*]',
  extended_valid_elements: '*[*]',
  verify_html: false,
  // Direct image upload straight into the editor -> saved into /images.
  automatic_uploads: true,
  images_upload_url: '/admin/upload_json.php',
  images_upload_credentials: true,
  content_style: 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:15px;line-height:1.7} img{max-width:100%;height:auto}'
});
</script>
