@php
  // Resolve images robustly for DomPDF
  $frontRoot = realpath(base_path('../frontend/public/assets/images')) ?: null;
  $backRoot = public_path('assets/images');

  $leftCandidates = [];
  $rightCandidates = [];

  // Consider both PNG and JPG filenames
  $leftNames = ['ngao.png', 'ngao.jpg'];
  $rightNames = ['logo.png', 'logo.jpg'];

  if ($frontRoot) {
    foreach ($leftNames as $name) { $leftCandidates[] = $frontRoot . DIRECTORY_SEPARATOR . $name; }
    foreach ($rightNames as $name) { $rightCandidates[] = $frontRoot . DIRECTORY_SEPARATOR . $name; }
  }

  // Laravel public path
  if ($backRoot) {
    foreach ($leftNames as $name) { $leftCandidates[] = $backRoot . DIRECTORY_SEPARATOR . $name; }
    foreach ($rightNames as $name) { $rightCandidates[] = $backRoot . DIRECTORY_SEPARATOR . $name; }
  }


  $pickExisting = function(array $paths){
      foreach ($paths as $p) { if ($p && file_exists($p)) return $p; }
      return null;
  };
  $leftPath = $pickExisting($leftCandidates);
  $rightPath = $pickExisting($rightCandidates);

  $toDataUri = function($path){
      if ($path && file_exists($path)) {
          $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
          $mime = function_exists('mime_content_type') ? @mime_content_type($path) : null;
          if (!$mime) {
              $mime = in_array($ext, ['jpg','jpeg']) ? 'image/jpeg' : 'image/png';
          }
          $data = base64_encode(@file_get_contents($path));
          if ($data) return 'data:' . $mime . ';base64,' . $data;
      }
      return null;
  };

  $leftSrc = $toDataUri($leftPath);
  $rightSrc = $toDataUri($rightPath);
  // Fallback to file:// path if data URI fails
  if (!$leftSrc && $leftPath) $leftSrc = 'file:///' . str_replace('\\','/', realpath($leftPath));
  if (!$rightSrc && $rightPath) $rightSrc = 'file:///' . str_replace('\\','/', realpath($rightPath));
@endphp
<div class="row hdr">
  <div class="col hcol" style="text-align:left">
    @if($leftSrc)
      <img src="{!! $leftSrc !!}" style="height:80px;" />
    @endif
  </div>
  <div class="col hcol hdr-center-middle">
    <div class="hdr-title">MINISTRY OF HEALTH</div>
    <div class="hdr-sub">MUHIMBILI NATIONAL HOSPITAL</div>
    <div class="hdr-sub">MLOGANZILA</div>
  </div>
  <div class="col hcol" style="text-align:right">
    @if($rightSrc)
      <img src="{!! $rightSrc !!}" style="height:80px;" />
    @endif
  </div>
</div>
<div class="rule rule-top"></div>
<div class="rule rule-heavy"></div>
<div class="rule rule-bottom"></div>
