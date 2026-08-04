# Informe de decisiones Promarine

Generado: {{ now()->toIso8601String() }}

@foreach(['approved'=>'Decisiones aprobadas','pending'=>'Decisiones pendientes','blocked'=>'Bloqueos','needs_review'=>'Requieren revisión'] as $state=>$heading)
## {{$heading}}
@foreach($questions->where('status',$state) as $q)
- **{{$q->question}}** — {{$q->answer?->answer ?: 'Sin respuesta'}} ({{$q->section->name}}, impacto {{$q->impact}})
@endforeach
@endforeach

## Integraciones pendientes
- Mercado Pago: mock, sin credenciales reales.
- Shopify: mock, sin creación de pedidos reales.
- IGS: mock, sin envío de ventas reales.
