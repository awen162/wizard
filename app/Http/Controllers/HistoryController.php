<?php
/**
 * wizard
 *
 * @link      https://www.yunsom.com/
 * @copyright 管宜尧 <guanyiyao@yunsom.com>
 */

namespace App\Http\Controllers;


use App\Repositories\Document;
use App\Repositories\DocumentHistory;
use App\Repositories\Project;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    /**
     * 文档编辑历史
     *
     * @param Request $request
     * @param         $id
     * @param         $page_id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function pages(Request $request, $id, $page_id)
    {
        $page = Document::where('project_id', $id)->where('id', $page_id)->firstOrFail();
        /** @var Project $project */
        $project = Project::with([
            'pages' => function (Relation $query) {
                $query->select('id', 'pid', 'title', 'description', 'project_id', 'type', 'status');
            }
        ])->findOrFail($id);

        $histories = DocumentHistory::with('operator')
            ->where('page_id', $page_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('doc.history', [
            'histories'  => $histories,
            'project'    => $project,
            'pageID'     => $page_id,
            'pageItem'   => $page,
            'navigators' => navigator($project->pages, $id, $page_id)
        ]);
    }

    /**
     * 历史文档查看
     *
     * @param Request $request
     * @param         $id
     * @param         $page_id
     * @param         $history_id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function page(Request $request, $id, $page_id, $history_id)
    {
        $page = Document::where('project_id', $id)->where('id', $page_id)->firstOrFail();
        /** @var Project $project */
        $project = Project::with([
            'pages' => function (Relation $query) {
                $query->select('id', 'pid', 'title', 'description', 'project_id', 'type', 'status');
            }
        ])->findOrFail($id);

        $history = DocumentHistory::where('page_id', $page_id)
            ->where('id', $history_id)->firstOrFail();

        return view('doc.history-doc', [
            'history'    => $history,
            'project'    => $project,
            'pageID'     => $page_id,
            'pageItem'   => $page,
            'navigators' => navigator($project->pages, $id, $page_id)
        ]);
    }

    /**
     * 从历史页面恢复
     *
     * @param Request $request
     * @param         $id
     * @param         $page_id
     * @param         $history_id
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function recover(Request $request, $id, $page_id, $history_id)
    {
        $pageItem = Document::where('project_id', $id)->where('id', $page_id)->firstOrFail();
        $this->authorize('page-edit', $pageItem);

        $historyItem = DocumentHistory::where('project_id', $id)->where('id', $history_id)
            ->where('page_id', $page_id)->firstOrFail();

        Document::recover($pageItem, $historyItem);
        $this->alert('文档恢复成功');

        return redirect(wzRoute('project:home', ['id' => $id, 'p' => $page_id]));
    }

    /**
     * 以JSON返回历史文档
     *
     * @param $id
     * @param $page_id
     * @param $history_id
     *
     * @return array
     */
    public function getPageJSON($id, $page_id, $history_id)
    {
        $history = DocumentHistory::where('page_id', $page_id)->where('id', $history_id)
            ->firstOrFail();

        return [
            'id'                     => $history->id,
            'page_id'                => $history->page_id,
            'pid'                    => $history->pid,
            'title'                  => $history->title,
            'description'            => $history->description,
            'content'                => $history->content,
            'type'                   => $history->type,
            'user_id'                => $history->user_id,
            'username'               => $history->user->name,
            'last_modified_user_id'  => $history->operator->id,
            'last_modified_username' => $history->operator->name,
            'created_at'             => $history->created_at->format('Y-m-d H:i:s'),
        ];
    }
}