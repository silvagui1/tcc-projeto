<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alunos</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 640px; margin: 40px auto; padding: 0 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        form.inline { display: inline; }
        .status { background: #e6f4ea; padding: 8px 12px; border-radius: 6px; }
        .erro { color: #b00020; }
    </style>
</head>
<body>
    <h1>Alunos</h1>

    @if (session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul class="erro">
            @foreach ($errors->all() as $erro)
                <li>{{ $erro }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('aluno.add') }}">
        @csrf
        <input type="text" name="nome" placeholder="Nome do aluno" required>
        <button type="submit">Cadastrar</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($alunos as $aluno)
                <tr>
                    <td>{{ $aluno->id }}</td>
                    <td>
                        <form class="inline" method="POST" action="{{ route('aluno.edit') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ $aluno->id }}">
                            <input type="text" name="nome" value="{{ $aluno->nome }}">
                            <button type="submit">Salvar</button>
                        </form>
                    </td>
                    <td>
                        <form class="inline" method="POST" action="{{ route('aluno.remove') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ $aluno->id }}">
                            <button type="submit">Remover</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Nenhum aluno cadastrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
