<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Pterodactyl\Repositories;

use Cron;
use Validator;

use Pterodactyl\Models;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;

class TaskRepository
{

    protected $defaults = [
        'year' => '*',
        'day_of_week' => '*',
        'month' => '*',
        'day_of_month' => '*',
        'hour' => '*',
        'minute' => '*/30',
    ];

    protected $actions = [
        'command',
        'power',
    ];

    public function __construct()
    {
        //
    }

    /**
     * Create a new scheduled task for a given server.
     * @param  int      $id
     * @param  array    $data
     *
     * @throws DisplayException
     * @throws DisplayValidationException
     * @return void
     */
    public function create($id, $data)
    {
        $server = Models\Server::findOrFail($id);

        $validator = Validator::make($data, [
            'action' => 'string|required',
            'data' => 'string|required',
            'year' => 'string|sometimes|required',
            'day_of_week' => 'string|sometimes|required',
            'month' => 'string|sometimes|required',
            'day_of_month' => 'string|sometimes|required',
            'hour' => 'string|sometimes|required',
            'minute' => 'string|sometimes|required'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        if (!in_array($data['action'], $this->actions)) {
            throw new DisplayException('The action provided is not valid.');
        }

        $cron = $this->defaults;
        foreach ($this->defaults as $setting => $value) {
            if (array_key_exists($setting, $data)) {
                $cron[$setting] = $data[$setting];
            }
        }

        // Check that is this a valid Cron Entry
        try {
            $buildCron = Cron::factory(sprintf('%s %s %s %s %s %s',
                $cron['minute'],
                $cron['hour'],
                $cron['day_of_month'],
                $cron['month'],
                $cron['day_of_week'],
                $cron['year']
            ));
        } catch (\Exception $ex) {
            throw new DisplayException($ex);
        }

        $task = new Models\Task;
        $task->fill([
            'server' => $server->id,
            'active' => 1,
            'action' => $data['action'],
            'data' => $data['data'],
            'queued' => 0,
            'year' => $cron['year'],
            'day_of_week' => $cron['day_of_week'],
            'month' => $cron['month'],
            'day_of_month' => $cron['day_of_month'],
            'hour' => $cron['hour'],
            'minute' => $cron['minute'],
            'last_run' => null,
            'next_run' => $buildCron->getNextRunDate()
        ]);

        return $task->save();

    }

}