# Firebase Backend Setup

**⚠️ IMPORTANT NOTE**: This document describes an **alternative backend implementation** using Firebase Cloud Functions and Firestore, separate from the Laravel API. As of the latest update, the **mobile app has removed Firebase SDK dependencies** and relies entirely on the Laravel REST API. This document is kept for reference only if a Firebase backend alternative is needed in the future.

**Current Status**: The mobile app uses Laravel API exclusively. Firebase SDK has been removed from the mobile app.

---

This repo now supports running the mobile app against either the existing Laravel API or a Firebase backend. The Firebase path relies on Firestore for data storage and Cloud Functions for business logic that used to live in the Laravel scheduler/queues.

## 1. Enable Firebase services

1. Upgrade the Firebase project to the **Blaze** plan (required for Cloud Scheduler and Functions billing, even if usage stays within free tier).
2. Enable the following APIs in Google Cloud Console:
   - Cloud Firestore (in Native mode)
   - Cloud Functions
   - Cloud Scheduler
   - Pub/Sub
3. From the repo root run `firebase login` and `firebase use <project-id>`.

## 2. Firestore data model

| Collection | Document ID | Notes |
|------------|-------------|-------|
| `groups` | string (e.g. `"6"` to mirror current IDs) | contains fields such as `name`, `ownerId`, `totalAmountCents`, `cadence`, `dueDay`, `graceDays`, `timezone`, etc. |
| `groups/{groupId}/members` | auto-ID | store `userId`, `shareType`, `shareValue`, `preferredChannel`, `venmoUsername`, `cashTag`, `cashPhone`, `cashEmail`, `status`. |
| `users` | Firebase UID (or legacy numeric id converted to string) | include email, phone, preferred payment method metadata. |
| `billingCycles` | auto-ID | reference the group, `status`, `dueAt`, `nextRunAt`, `totalAmountCents`. Optionally use a subcollection `billingCycles/{cycleId}/paymentRequests`. |
| `jobs` (optional) | auto-ID | queue documents used to fan out payment reminder work. |

When importing legacy data migrate the Laravel numeric IDs into string keys on the Firestore documents so the mobile app keeps working with the same identifiers.

## 3. Cloud Functions project structure

Create a `firebase/functions` workspace:

```bash
cd firebase
firebase init functions --typescript
```

Replace `functions/src/index.ts` contents with the snippets below. Install dependencies:

```bash
cd functions
npm install firebase-admin firebase-functions
```

### addGroupMember callable (used by the mobile app)

```ts
import * as functions from 'firebase-functions';
import * as admin from 'firebase-admin';

admin.initializeApp();
const db = admin.firestore();

export const addGroupMember = functions
  .region(process.env.FUNCTIONS_REGION || 'us-central1')
  .https.onCall(async (data, context) => {
    const { groupId, payload } = data ?? {};
    if (!groupId || !payload) {
      throw new functions.https.HttpsError(
        'invalid-argument',
        'Missing groupId or payload.'
      );
    }

    const groupRef = db.collection('groups').doc(String(groupId));
    const membersRef = groupRef.collection('members');

    const email = (payload.email ?? '').toString().trim().toLowerCase();
    if (!email) {
      throw new functions.https.HttpsError(
        'invalid-argument',
        'Member email is required.'
      );
    }

    const existing = await membersRef.where('email', '==', email).limit(1).get();
    if (!existing.empty) {
      throw new functions.https.HttpsError(
        'already-exists',
        'User is already a member of this group.',
      );
    }

    const doc = await membersRef.add({
      ...payload,
      email,
      status: 'active',
      createdAt: admin.firestore.FieldValue.serverTimestamp(),
      updatedAt: admin.firestore.FieldValue.serverTimestamp(),
    });

    return {
      message: 'Member added successfully',
      member: {
        id: doc.id,
        ...payload,
      },
    };
  });
```

### Scheduled payment-cycle runner

```ts
const PAYMENT_QUEUE_TOPIC = 'payment-requests';

export const enqueuePaymentCycles = functions
  .region(process.env.FUNCTIONS_REGION || 'us-central1')
  .pubsub.schedule('every 1 hours')
  .timeZone('America/New_York')
  .onRun(async () => {
    const now = admin.firestore.Timestamp.now();
    const snap = await db
      .collection('billingCycles')
      .where('status', '==', 'pending')
      .where('nextRunAt', '<=', now)
      .limit(200)
      .get();

    const messages = snap.docs.map((doc) => ({
      cycleId: doc.id,
      groupId: doc.get('groupId'),
    }));

    const pubsub = admin.pubsub();
    await Promise.all(
      messages.map((msg) =>
        pubsub.topic(PAYMENT_QUEUE_TOPIC).publishMessage({ json: msg }),
      ),
    );

    return {
      queued: messages.length,
    };
  });
```

### Worker for individual payment requests

```ts
export const processPaymentRequest = functions
  .region(process.env.FUNCTIONS_REGION || 'us-central1')
  .pubsub.topic(PAYMENT_QUEUE_TOPIC)
  .onPublish(async (message) => {
    const { cycleId, groupId } = message.json;
    if (!cycleId || !groupId) {
      functions.logger.warn('Malformed payment job', message.json);
      return;
    }

    const cycleRef = db.collection('billingCycles').doc(String(cycleId));
    const cycleSnap = await cycleRef.get();
    if (!cycleSnap.exists) return;

    const membersSnap = await db
      .collection('groups')
      .doc(String(groupId))
      .collection('members')
      .where('status', '==', 'active')
      .get();

    const batch = db.batch();
    for (const member of membersSnap.docs) {
      const prRef = cycleRef.collection('paymentRequests').doc();
      batch.set(prRef, {
        memberId: member.id,
        email: member.get('email'),
        shareType: member.get('shareType'),
        shareValue: member.get('shareValue'),
        status: 'pending',
        createdAt: admin.firestore.FieldValue.serverTimestamp(),
      });
    }

    batch.update(cycleRef, {
      status: 'processing',
      lastRunAt: admin.firestore.FieldValue.serverTimestamp(),
    });
    await batch.commit();
  });
```

Deploy with:

```bash
cd firebase/functions
firebase deploy --only functions
```

## 4. Running the mobile app against Firebase

1. Make sure the Flutter app has `google-services.json` / `GoogleService-Info.plist` configured (already present in the repo).
2. Run with the Firebase backend flag:

```bash
flutter run \
  --dart-define=USE_FIREBASE_BACKEND=true \
  --dart-define=FIREBASE_FUNCTIONS_REGION=us-central1
```

3. When the flag is false (default) the app continues to talk to the Laravel API via Dio.

## 5. Next steps

- Expand the `FirebaseBackendService` with Firestore queries for payment requests, nudge/confirm flows, and group editing.
- Migrate existing Laravel data into Firestore using an export script (read from MySQL, write to Firestore matching the data model above).
- Gradually move scheduled jobs from Laravel to Cloud Functions using the templates here, then decommission the Laravel cron workers once parity is reached.
