/**
 * Collection of cron events.
 */
VjaJS.EventsCollection = BaseCollection.extend( {
    action: 'cron_pixie_events',
    model: CronPixie.EventModel
} );
