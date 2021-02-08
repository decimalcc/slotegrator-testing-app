<?php

namespace App\Http\Controllers;

use App\Http\Requests\WithdrawRequest;
use App\Models\GoodPrize;
use App\Models\MoneyPrize;
use App\Models\PointsPrize;
use App\Models\Winning;
use App\Services\PrizeTypeRandomizer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PrizeController extends Controller
{

    public function dashboard()
    {
        $winnings = Winning::query()
            ->where('user_id', auth()->id())
            ->with('prize')
            ->get();

        return view('dashboard', compact('winnings'));
    }

    public function getPrize(PrizeTypeRandomizer $randomizer)
    {
        // this can be moved to separate service provider
        $randomizer->registerType(MoneyPrize::class);
        $randomizer->registerType(PointsPrize::class);
        $randomizer->registerType(GoodPrize::class);

        /**
         * @var \Illuminate\Database\Eloquent\Model|\App\Contracts\Prize $prize
         */
        $prize = $randomizer->run();
        $prize->generate();
        $prize->save();

        (new Winning())
            ->user()->associate(auth()->user())
            ->prize()->associate($prize)
            ->save();

        session()->flash('prize', trans('prize.messages.won', ['prize' => $prize->name()]));

        return redirect()->back();
    }

    public function withdrawMoney(WithdrawRequest $request, MoneyPrize $moneyPrize)
    {
        if ($moneyPrize->is_withdrawn) {
            throw new BadRequestHttpException('The money has already been withdrawn.');
        }

        try {
            // todo: bank API request
            $moneyPrize->is_withdrawn = true;
            $moneyPrize->save();
        }
        catch (\Exception $e) {
            // say something to user
        }

        return redirect()->back();
    }
}
