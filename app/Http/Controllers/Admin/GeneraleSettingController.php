<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Currency;
use App\Models\GeneraleSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\AiPromptRequest;
use Illuminate\Support\Facades\Artisan;
use App\Http\Requests\GeneraleSettingRequest;
use App\Repositories\GeneraleSettingRepository;

class GeneraleSettingController extends Controller
{
    /**
     * Display a listing of the generale settings.
     */
    public function index()
    {
        $currencies = Currency::all();

        return view('admin.generale-setting', compact('currencies'));
    }

    /**
     * Update the generale settings.
     */
    public function update(GeneraleSettingRequest $request)
    {
        // store generale settings from generaleSettingRepository
        GeneraleSettingRepository::updateByRequest($request);

        return back()->withSuccess(__('Generale settings updated successfully'));
    }

    /**
     * Run the latest update script.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCommand()
    {
        $commands = config('installer.update_commands');

        $errors = [];

        $changeToBasePath = 'cd ' . base_path();
        foreach ($commands as $command) {
            try {
                shell_exec($changeToBasePath . ' && ' . $command);
            } catch (\Throwable $th) {
                $errors[] = $th->getMessage();
            }
        }

        if (!empty($errors)) {
            return back()->with('runUpdateScriptError', $errors);
        }

        return back()->withSuccess(__('Latest Script Run Successfully'));
    }


    public function aiPromptIndex()
    {
        return view('admin.aiPrompt.index');
    }

    public function aiPromptUpdate(AiPromptRequest $request)
    {
        GeneraleSettingRepository::updateByAiPromptRequest($request);
        return back()->withSuccess(__('AI Prompt updated successfully'));
    }

    public function aiPromptConfigure()
    {
        return view('admin.aiPrompt.configure');
    }
    public function aiPromptConfigureUpdate(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:openai,gemini',
            'api_key' => 'required_if:provider,openai|nullable|string',
            'organization' => 'nullable|string',
            'gemini_api_key' => 'required_if:provider,gemini|nullable|string',
            'gemini_model' => 'nullable|string',
        ]);

        try {
            $this->setEnv('AI_PROVIDER', $request->provider);

            if ($request->filled('api_key')) {
                $this->setEnv('OPENAI_API_KEY', $request->api_key);
            }

            if ($request->has('organization')) {
                $this->setEnv('OPENAI_ORGANIZATION', $request->organization ?? '');
            }

            if ($request->filled('gemini_api_key')) {
                $this->setEnv('GEMINI_API_KEY', $request->gemini_api_key);
            }

            if ($request->filled('gemini_model')) {
                $this->setEnv('GEMINI_MODEL', $request->gemini_model);
            }

            $generaleSetting = GeneraleSetting::first();
            GeneraleSetting::query()->updateOrCreate(
                ['id' => $generaleSetting?->id ?? null],
                ['ai_provider' => $request->provider]
            );

            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            return back()->withSuccess(__('AI Prompt updated successfully'));

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
