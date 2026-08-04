@php
    $statusLabels = ['pending'=>'Pendiente','answered'=>'Respondida','needs_review'=>'Revisar','approved'=>'Aprobada','rejected'=>'Descartada','blocked'=>'Bloqueada'];
    $impactLabels = ['low'=>'Bajo','medium'=>'Medio','high'=>'Alto','critical'=>'Crítico'];
@endphp
<x-admin-shell>
    @if(session('ok'))<div class="pm-admin-flash" role="status">✓ {{session('ok')}}</div>@endif

    <header class="pm-interview-header">
        <div><span class="pm-admin-eyebrow">Centro de decisiones</span><h1>Cuestionarios para Tamara</h1><p>Respondé cada definición y marcá si queda aprobada, necesita revisión o está bloqueada.</p></div>
        <a href="/admin/interview/report" class="pm-admin-button pm-admin-button--outline">Ver informe</a>
    </header>

    <section class="pm-interview-stats" aria-label="Estado de las preguntas">
        <a href="/admin/interview?status=pending"><span>Pendientes</span><strong>{{$statusCounts['pending'] ?? 0}}</strong></a>
        <a href="/admin/interview?status=answered"><span>Respondidas</span><strong>{{$statusCounts['answered'] ?? 0}}</strong></a>
        <a href="/admin/interview?status=needs_review"><span>Para revisar</span><strong>{{$statusCounts['needs_review'] ?? 0}}</strong></a>
        <a href="/admin/interview?status=approved"><span>Aprobadas</span><strong>{{$statusCounts['approved'] ?? 0}}</strong></a>
    </section>

    <form method="get" action="/admin/interview" class="pm-interview-filters">
        <label class="pm-interview-search"><span aria-hidden="true">⌕</span><input name="q" value="{{$filters['q'] ?? ''}}" placeholder="Buscar una pregunta…"></label>
        <select name="status" aria-label="Filtrar por estado"><option value="">Todos los estados</option>@foreach($statusLabels as $value=>$label)<option value="{{$value}}" @selected(($filters['status'] ?? '')===$value)>{{$label}}</option>@endforeach</select>
        <select name="impact" aria-label="Filtrar por impacto"><option value="">Todos los impactos</option>@foreach($impactLabels as $value=>$label)<option value="{{$value}}" @selected(($filters['impact'] ?? '')===$value)>{{$label}}</option>@endforeach</select>
        <button>Filtrar</button>
        @if($filters)<a href="/admin/interview">Limpiar</a>@endif
    </form>

    <div class="pm-interview-result-count">Mostrando <b>{{$visibleQuestions}}</b> de {{$totalQuestions}} preguntas</div>

    <div class="pm-interview-sections">
        @forelse($sections as $section)
            <section class="pm-interview-section" x-data="{open:{{$loop->first ? 'true' : 'false'}}}">
                <button type="button" class="pm-interview-section__toggle" @click="open=!open" :aria-expanded="open">
                    <span><small>Sección {{$loop->iteration}}</small><strong>{{$section->name}}</strong></span>
                    <span class="pm-interview-section__count">{{$section->questions->where('status','!=','pending')->count()}} / {{$section->questions->count()}} <i :class="open ? 'is-open' : ''">⌄</i></span>
                </button>
                <div x-show="open" x-cloak class="pm-question-list">
                    @foreach($section->questions as $question)
                        <article class="pm-question-card" id="pregunta-{{$question->id}}">
                            <form method="post" action="/admin/interview/{{$question->id}}">
                                @csrf
                                <div class="pm-question-meta"><span class="is-{{$question->status}}">{{$statusLabels[$question->status] ?? $question->status}}</span><span class="impact-{{$question->impact}}">Impacto {{$impactLabels[$question->impact] ?? $question->impact}}</span>@if($question->answer?->answered_at)<time>Actualizada {{$question->answer->answered_at->diffForHumans()}}</time>@endif</div>
                                <label class="pm-question-title" for="answer-{{$question->id}}"><span>{{$question->position}}</span>{{$question->question}}</label>
                                @if($question->why_it_matters)<p class="pm-question-help">{{$question->why_it_matters}}</p>@endif
                                <textarea id="answer-{{$question->id}}" name="answer" placeholder="Escribí una respuesta parcial o definitiva…">{{$question->answer?->answer}}</textarea>
                                <input name="comment" value="{{$question->answer?->comment}}" placeholder="Comentario interno o dato pendiente (opcional)">
                                <div class="pm-question-actions">
                                    <label><span>Estado</span><select name="status">@foreach($statusLabels as $value=>$label)<option value="{{$value}}" @selected($question->status===$value)>{{$label}}</option>@endforeach</select></label>
                                    <button type="submit">Guardar respuesta <span aria-hidden="true">→</span></button>
                                </div>
                            </form>
                            @if($question->answers->count() > 1)
                                <details class="pm-question-history"><summary>Ver historial ({{$question->answers->count()}} versiones)</summary><div>@foreach($question->answers->take(5) as $answer)<article><time>{{$answer->answered_at?->format('d/m/Y H:i')}}</time><span>{{$statusLabels[$answer->status] ?? $answer->status}}</span><p>{{$answer->answer ?: 'Sin texto'}}</p></article>@endforeach</div></details>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="pm-interview-empty"><span>⌕</span><h2>No encontramos preguntas</h2><p>Probá quitando algún filtro o usando otra búsqueda.</p><a href="/admin/interview">Mostrar todas</a></div>
        @endforelse
    </div>
</x-admin-shell>
