<h2>Nouvelle demande de partenariat</h2>
<p><strong>Organisation :</strong> {{ $data['company'] }}</p>
<p><strong>Contact :</strong> {{ $data['name'] }} ({{ $data['email'] }}, {{ $data['phone'] }})</p>
<p><strong>Formule souhaitée :</strong> {{ ucfirst($data['formula']) }}</p>
<p><strong>Message :</strong><br>{!! nl2br(e($data['message'] ?? '—')) !!}</p>  