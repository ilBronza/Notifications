<?php

namespace IlBronza\Notifications\Facades;

class ScheduledChannelManager extends ChannelManager
{
    /**
     * Create an instance of the database driver.
     *
     * @return \Illuminate\Notifications\Channels\DatabaseChannel
     */
    protected function createDatabaseDriver()
    {
        return $this->container->make(ScheduledDatabaseChannel::class);
    }

}
