<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CertificationStatus;
use App\Http\Requests\QaThread\IndexRequest;
use App\Http\Requests\QaThread\StoreRequest;
use App\Http\Requests\QaThread\UpdateRequest;
use App\Models\Certification;
use App\Models\QaThread;
use App\UseCases\QaThread\DestroyAction;
use App\UseCases\QaThread\IndexAction;
use App\UseCases\QaThread\ResolveAction;
use App\UseCases\QaThread\ShowAction;
use App\UseCases\QaThread\StoreAction;
use App\UseCases\QaThread\UnresolveAction;
use App\UseCases\QaThread\UpdateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QaThreadController extends Controller
{
    public function index(IndexRequest $request, IndexAction $action): View
    {
        $validated = $request->validated();

        $result = $action(
            viewer: $request->user(),
            filter: $validated,
        );

        return view('qa-thread.index', [
            'threads' => $result['threads'],
            'certifications' => $result['certifications'],
            'filters' => $validated,
            'publishedStatus' => CertificationStatus::Published,
        ]);
    }

    public function show(QaThread $thread, ShowAction $action): View
    {
        $this->authorize('view', $thread);

        $thread = $action($thread);

        return view('qa-thread.show', [
            'thread' => $thread,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', QaThread::class);

        $certifications = Certification::query()
            ->published()
            ->orderBy('name')
            ->get();

        return view('qa-thread.create', [
            'certifications' => $certifications,
        ]);
    }

    public function store(StoreRequest $request, StoreAction $action): RedirectResponse
    {
        $thread = $action(
            user: $request->user(),
            validated: $request->validated(),
        );

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を投稿しました。');
    }

    public function edit(QaThread $thread): View
    {
        $this->authorize('update', $thread);

        return view('qa-thread.edit', [
            'thread' => $thread,
        ]);
    }

    public function update(QaThread $thread, UpdateRequest $request, UpdateAction $action): RedirectResponse
    {
        $action(
            thread: $thread,
            validated: $request->validated(),
        );

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を更新しました。');
    }

    public function destroy(QaThread $thread, DestroyAction $action): RedirectResponse
    {
        $this->authorize('delete', $thread);

        $action($thread);

        $isAdminContext = request()->routeIs('admin.*');

        return redirect()
            ->route(
                $isAdminContext
                ? 'admin.qa-board.index'
                : 'qa-board.index',
            )
            ->with('success', '質問を削除しました。');
    }

    public function resolve(QaThread $thread, ResolveAction $action): RedirectResponse
    {
        $this->authorize('resolve', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を解決済みにしました。');
    }

    public function unresolve(QaThread $thread, UnresolveAction $action): RedirectResponse
    {
        $this->authorize('unresolve', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を未解決に戻しました。');
    }
}
