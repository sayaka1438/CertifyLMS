<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AiChatMessageStatus;
use App\Http\Requests\AiChat\StoreConversationRequest;
use App\Http\Requests\AiChat\StoreMessageRequest;
use App\Http\Requests\AiChat\UpdateConversationRequest;
use App\Models\AiChatConversation;
use App\UseCases\AiChat\DestroyConversationAction;
use App\UseCases\AiChat\IndexAction;
use App\UseCases\AiChat\ShowAction;
use App\UseCases\AiChat\StoreConversationAction;
use App\UseCases\AiChat\StoreMessageAction;
use App\UseCases\AiChat\UpdateConversationAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiChatController extends Controller
{
    public function index(
        Request $request,
        IndexAction $action,
    ): View|RedirectResponse {
        $this->authorize('viewAny', AiChatConversation::class);

        $conversations = $action($request->user());

        if ($conversations->isNotEmpty()) {
            return redirect()->route(
                'ai-chat.conversations.show',
                $conversations->first(),
            );
        }

        return view('ai-chat.empty-state', [
            'conversations' => $conversations,
        ]);
    }

    public function store(
        StoreConversationRequest $request,
        StoreConversationAction $action,
        StoreMessageAction $storeMessage,
    ): JsonResponse|RedirectResponse {
        $this->authorize('viewAny', AiChatConversation::class);

        $conversation = $action(
            $request->user(),
            $request->validated(),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'conversation' => $conversation,
            ], $conversation->wasRecentlyCreated ? 201 : 200);
        }

        $message = $request->validated('message');

        if (is_string($message) && $message !== '') {
            $storeMessage(
                $request->user(),
                $conversation,
                $message,
            );
        }

        return redirect()->route('ai-chat.conversations.show', $conversation);
    }

    public function show(
        Request $request,
        AiChatConversation $conversation,
        ShowAction $action,
    ): View|JsonResponse {
        $this->authorize('view', $conversation);

        $conversation = $action($conversation);

        if ($request->expectsJson()) {
            return response()->json([
                'conversation' => $conversation,
                'messages' => $conversation->messages,
            ]);
        }

        return view('ai-chat.show', [
            'conversation' => $conversation,
        ]);
    }

    public function update(
        UpdateConversationRequest $request,
        AiChatConversation $conversation,
        UpdateConversationAction $action,
    ): RedirectResponse {
        $action(
            conversation: $conversation,
            title: $request->validated('title'),
        );

        return redirect()
            ->route('ai-chat.conversations.show', $conversation)
            ->with('success', 'タイトルを更新しました。');
    }

    public function destroy(
        AiChatConversation $conversation,
        DestroyConversationAction $action,
    ): RedirectResponse {
        $this->authorize('delete', $conversation);

        $action($conversation);

        return redirect()
            ->route('ai-chat.index')
            ->with('success', '会話を削除しました。');
    }

    public function storeMessage(
        StoreMessageRequest $request,
        AiChatConversation $conversation,
        StoreMessageAction $action,
    ): JsonResponse {
        $result = $action(
            $request->user(),
            $conversation,
            $request->validated('content'),
        );

        $status = $result['assistant_message']->status === AiChatMessageStatus::Error
            ? 502
            : 200;

        return response()->json($result, $status);
    }
}
